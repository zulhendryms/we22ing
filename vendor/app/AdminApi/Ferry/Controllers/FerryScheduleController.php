<?php

namespace App\AdminApi\Ferry\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Ferry\Entities\FerrySchedule;
use App\Core\Ferry\Entities\FerryPricing;
use App\Core\Ferry\Resources\FerryScheduleResource;
use App\Core\Ferry\Resources\FerryScheduleCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Services\AutoNumberService;
use Validator;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class FerryScheduleController extends Controller
{
    private $crudController;
    protected $roleService;
    public function __construct(
        RoleModuleService $roleService
    )
    {
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
    }

    public function fields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = ['w'=> 180, 'n'=>'Period',];
        $fields[] = ['w'=> 180, 'n'=>'BusinessPartner', 'f'=>'bp.Name'];
        $fields[] = ['w'=> 250, 'n'=>'PortFrom',        'f'=>'pf.Name'];
        $fields[] = ['w'=> 90,  'n'=>'PortTo',          'f'=>'pt.Name'];
        return $fields;
    }

    public function config(Request $request) {
        // $fields = $this->fields();
        $fields = $this->crudController->jsonConfig($this->fields(),false,true);
        foreach ($fields as &$row) { //combosource
        if ($row->headerName == 'Company') $row->source = comboselect('company');
        };
        return $fields;
    }

    public function list(Request $request) {
        $fields = $this->crudController->jsonConfig($this->fields(),false,true);
        $data = DB::table('ferferryschedule as data')
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
            ->leftJoin('ferbusinesspartnerport AS bpp', 'bpp.Oid', '=', 'data.BusinessPartnerPort')
            ->leftJoin('mstbusinesspartner AS bp', 'bp.Oid', '=', 'bpp.BusinessPartner')
            ->leftJoin('ferroute AS r', 'r.Oid', '=', 'data.Route')
            ->leftJoin('ferport AS pf', 'pf.Oid', '=', 'r.PortFrom')
            ->leftJoin('ferport AS pt', 'pt.Oid', '=', 'r.PortTo')
            ;
        $data = $this->crudController->jsonList($data, $this->fields(), $request, 'ferferrypricing','Period');
        $role = $this->roleService->list('FerrySchedule'); //rolepermission
        foreach($data as $row) $row->Action = $this->roleService->generateActionMaster($role);
        return $this->crudController->jsonListReturn($data, $fields);
    }

    public function index(Request $request)
    {        
        try {
            $user = Auth::user();
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);            
            $query = "SELECT fs.Oid, fs.Period, bp.Code AS BusinessPartner, CONCAT(pf.Code, ' - ', pt.Code) AS Route
                FROM ferferryschedule fs
                LEFT OUTER JOIN ferbusinesspartnerport bpp ON fs.BusinessPartnerPort = bpp.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON bpp.BusinessPartner = bp.Oid
                LEFT OUTER JOIN ferroute r ON fs.Route = r.Oid
                LEFT OUTER JOIN ferport pf ON r.PortFrom = pf.Oid
                LEFT OUTER JOIN ferport pt ON r.PortTo = pt.Oid
                WHERE fs.Period = '{$request->input('period')}' AND bp.oid = '{$request->input('businesspartner')}'
                GROUP BY bp.Code, pf.City, pf.Code, pt.City, pt.Code
                ORDER BY bp.Code, pf.City, pf.Code, pt.City, pt.Code";
            $data = DB::select($query);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }
    
    public function show(FerrySchedule $data)
    {
        try {    
            $data = FerrySchedule::with(['BusinessPartnerPortObj','BusinessPartnerPortObj.BusinessPartnerObj','RouteObj','RouteObj.PortFromObj','RouteObj.PortToObj'])->findOrFail($data->Oid);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF

        try {            
            if (!$Oid) $data = new FerrySchedule();
            else $data = FerrySchedule::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                $disabled = disabledFieldsForEdit();
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();
                if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'ferferryschedule');
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('FerrySchedule'); //rolepermission
            $data->Role = $this->roleService->generateActionMaster($role);
            $data->BusinessPartnerName = $data->BusinessPartnerObj->Name;
            $data->PortFromName = $data->PortFromObj->Name;
            $data->PortToName = $data->PortToObj->Name;

            return response()->json(
                $data, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(FerrySchedule $data)
    {
        try {            
            DB::transaction(function () use ($data) {
                $data->delete();
            });
            return response()->json(
                null, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function generateSearch(Request $request) {
        try {
            // $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
            // $dataArray = object_to_array($request);
    
            $query = "SELECT bp.Code AS BizPartner, CONCAT(pf.Code, ' - ', pt.Code) AS Route, COUNT(*) AS Time, MIN(Day1) AS Quantity,
                FORMAT(IFNULL(MAX(fs.Local1WAdult), 0), 0) AS LocalWeekday, FORMAT(IFNULL(MAX(fs.Local1WAdultWk), 0), 0) AS LocalWeekend,
                FORMAT(IFNULL(MAX(fs.Foreigner1WAdult), 0), 0) AS ForeignerWeekday, FORMAT(IFNULL(MAX(fs.Foreigner1WAdultWk), 0), 0) AS ForeignerWeekend
                FROM ferferryschedule fs
                LEFT OUTER JOIN ferbusinesspartnerport bpp ON fs.BusinessPartnerPort = bpp.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON bpp.BusinessPartner = bp.Oid
                LEFT OUTER JOIN ferroute r ON fs.Route = r.Oid
                LEFT OUTER JOIN ferport pf ON r.PortFrom = pf.Oid
                LEFT OUTER JOIN ferport pt ON r.PortTo = pt.Oid
                WHERE fs.Period = '{$request->input('PeriodFrom')}'
                GROUP BY bp.Code, pf.City, pf.Code, pt.City, pt.Code
                ORDER BY bp.Code, pf.City, pf.Code, pt.City, pt.Code";
            $data = DB::select($query);
            logger($query);
    
            return response()->json(
                $data, Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function generateProcess(Request $request) {
        try {
            // $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
            // $dataArray = object_to_array($request);
    
            $query = "INSERT INTO ferferryschedule (Oid, IsActive, CreatedAt, Company, Currency, 
                    BusinessPartnerPort, Route, Time, Period, Duration, TimeCutOff, DayCutOff,
                    DescriptionID, DescriptionEN, DescriptionCH,
                    Local1WAdult, Local1WChild, Local2WAdult, Local2WChild,
                    Foreigner1WAdult, Foreigner1WChild, Foreigner2WAdult, Foreigner2WChild,
                    Day1, Day2, Day3, Day4, Day5, Day6, Day7, Day8, Day9, Day10, 
                    Day11, Day12, Day13, Day14, Day15, Day16, Day17, Day18, Day19, Day20, 
                    Day21, Day22, Day23, Day24, Day25, Day26, Day27, Day28, Day29, Day30, Day31
                )
                SELECT UUID(), 1, NOW(), fs.Company, fs.Currency, fs.BusinessPartnerPort, fs.Route, fs.Time, 
                    '{$request->input('PeriodTo')}', fs.Duration, fs.TimeCutOff, fs.DayCutOff,
                    fs.DescriptionID, fs.DescriptionEN, fs.DescriptionCH,
                    fs.Local1WAdult, fs.Local1WChild, fs.Local2WAdult, fs.Local2WChild,
                    fs.Foreigner1WAdult, fs.Foreigner1WChild, fs.Foreigner2WAdult, fs.Foreigner2WChild,
                    fs.Day1, fs.Day2, fs.Day3, fs.Day4, fs.Day5, fs.Day6, fs.Day7, fs.Day8, fs.Day9, fs.Day10, 
                    fs.Day11, fs.Day12, fs.Day13, fs.Day14, fs.Day15, fs.Day16, fs.Day17, fs.Day18, fs.Day19, fs.Day20, 
                    fs.Day21, fs.Day22, fs.Day23, fs.Day24, fs.Day25, fs.Day26, fs.Day27, fs.Day28, fs.Day29, fs.Day30, fs.Day31
                FROM ferferryschedule fs
                    LEFT OUTER JOIN ferferryschedule fs2 ON fs2.Period = '{$request->input('PeriodTo')}'
                    AND fs2.BusinessPartnerPort = fs.BusinessPartnerPort
                    AND fs2.Route = fs.Route
                    AND fs2.Time = fs.Time
                    WHERE fs2.Oid IS NULL AND fs.Period = '{$request->input('PeriodFrom')}'";
            DB::insert($query);
            $data = FerryPricing::where('Period',$request->input('PeriodFrom'))->first();
            if ($data != null) {
                $query = "INSERT INTO ferferrypricing (Oid,Company,CreatedAt,
                    BusinessPartner,BusinessPartnerPort,Period,Route,Currency,
                    L1WAdultWkd,L1WChildWkd,L2WAdultWkd,L2WChildWkd,
                    F1WAdultWkd,F1WChildWkd,F2WAdultWkd,F2WChildWkd,
                    L1WAdultWke,L1WChildWke,L2WAdultWke,L2WChildWke,
                    F1WAdultWke,F1WChildWke,F2WAdultWke,F2WChildWke,
                    LCAdultWkd, LCChildWkd, LCAdultWke, LCChildWke, FCAdultWkd, FCChildWkd, FCAdultWke, FCChildWke
                )
                SELECT UUID(),fp.Company,NOW(),
                    fp.BusinessPartner,fp.BusinessPartnerPort,'{$request->input('PeriodTo')}',fp.Route,fp.Currency,
                    fp.L1WAdultWkd,fp.L1WChildWkd,fp.L2WAdultWkd,fp.L2WChildWkd,
                    fp.F1WAdultWkd,fp.F1WChildWkd,fp.F2WAdultWkd,fp.F2WChildWkd,
                    fp.L1WAdultWke,fp.L1WChildWke,fp.L2WAdultWke,fp.L2WChildWke,
                    fp.F1WAdultWke,fp.F1WChildWke,fp.F2WAdultWke,fp.F2WChildWke,
                    fp.LCAdultWkd, fp.LCChildWkd, fp.LCAdultWke, fp.LCChildWke, fp.FCAdultWkd, fp.FCChildWkd, fp.FCAdultWke, fp.FCChildWke
                    FROM ferferrypricing fp 
                    WHERE 
                    NOT EXISTS (SELECT * FROM ferferrypricing fps WHERE fp.BusinessPartnerPort = fps.BusinessPartnerPort AND fp.Route = fps.Route AND Period = '{$request->input('PeriodTo')}') 
                    AND fp.Period = '{$request->input('PeriodFrom')}'
                    GROUP BY fp.BusinessPartner,fp.BusinessPartnerPort,fp.Route,fp.Currency;";
                DB::insert($query);
            }
    
            return response()->json(
                null, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function updateSearch(Request $request) {
        try {
            $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
            $dataArray = object_to_array($request);            
            
            if ($request->Route != null) {
                $fs = FerrySchedule::where('Period',$request->Period)->first();
                if ($fs != null)
                {
                    //MASUKIN SEMUA PRICING DARI BULAN TERAKHIR
                    $fps = FerryPricing::where('Period','<=',$request->Period)->orderBy('Period','DESC')->get();
                    if ($fps != null)
                    {
                        $fp = $fps[0];                        
                        $query = "INSERT INTO ferferrypricing (
                            Oid,Company,BusinessPartner,BusinessPartnerPort,Period,Route,Currency,
                            L1WAdultWkd,L1WChildWkd,L2WAdultWkd,L2WChildWkd,
                            F1WAdultWkd,F1WChildWkd,F2WAdultWkd,F2WChildWkd,
                            L1WAdultWke,L1WChildWke,L2WAdultWke,L2WChildWke,
                            F1WAdultWke,F1WChildWke,F2WAdultWke,F2WChildWke,
                            LCAdultWkd,LCChildWkd,FCAdultWkd,FCChildWkd,LCAdultWke,LCChildWke,FCAdultWke,FCChildWke)
                        SELECT 
                            UUID(),fps.Company,fps.BusinessPartner,fps.BusinessPartnerPort,'{$request->Period}',fps.Route,fps.Currency,
                            L1WAdultWkd,L1WChildWkd,L2WAdultWkd,L2WChildWkd,
                            F1WAdultWkd,F1WChildWkd,F2WAdultWkd,F2WChildWkd,
                            L1WAdultWke,L1WChildWke,L2WAdultWke,L2WChildWke,
                            F1WAdultWke,F1WChildWke,F2WAdultWke,F2WChildWke,
                            LCAdultWkd,LCChildWkd,FCAdultWkd,FCChildWkd,LCAdultWke,LCChildWke,FCAdultWke,FCChildWke
                        FROM ferferrypricing fps
                        WHERE 
                            NOT EXISTS (SELECT * FROM ferferrypricing fp WHERE fp.BusinessPartnerPort = fps.BusinessPartnerPort AND fp.Route = fps.Route AND Period = '{$request->period}') 
                            AND fps.Period = '{$fp->Period}' AND fps.BusinessPartner = '{$request->BusinessPartner}'
                        GROUP BY fps.BusinessPartner, fps.BusinessPartnerPort";
                        DB::insert($query);
                    } else {                        
                        //MASUKIN SEMUA PRICING DARI SKEDUL SKARANG
                        $query = "INSERT INTO ferferrypricing (
                            Oid,Company,BusinessPartner,BusinessPartnerPort,Period,Route,Currency,
                            L1WAdultWkd,L1WChildWkd,L2WAdultWkd,L2WChildWkd,
                            F1WAdultWkd,F1WChildWkd,F2WAdultWkd,F2WChildWkd,
                            L1WAdultWke,L1WChildWke,L2WAdultWke,L2WChildWke,
                            F1WAdultWke,F1WChildWke,F2WAdultWke,F2WChildWke)
                        SELECT 
                            UUID(),fs.Company,bpp.BusinessPartner,fs.BusinessPartnerPort,'{$request->Period}',fs.Route,fs.Currency,
                            Local1WAdult,Local1WChild,Local2WAdult,Local2WChild,
                            Foreigner1WAdult,Foreigner1WChild,Foreigner2WAdult,Foreigner2WChild,
                            Local1WAdult,Local1WChild,Local2WAdult,Local2WChild,
                            Foreigner1WAdult,Foreigner1WChild,Foreigner2WAdult,Foreigner2WChild
                        FROM ferferryschedule fs
                            LEFT OUTER JOIN ferbusinesspartnerport bpp ON fs.BusinessPartnerPort = bpp.Oid
                        WHERE 
                            NOT EXISTS (SELECT * FROM ferferrypricing fp WHERE fp.BusinessPartnerPort = fs.BusinessPartnerPort AND fp.Route = fs.Route AND Period = '{$request->Period}') 
                            AND fs.Period = '{$request->Period}' AND bpp.BusinessPartner = '{$request->BusinessPartner}'
                        GROUP BY bpp.BusinessPartner, fs.BusinessPartnerPort;";
                        DB::insert($query);
                    }
                }
                $queryRoute = "";
                if ($request->Route != null) $queryRoute = "AND fs.Route = '".$request->Route."'";
                $query = "SELECT CONCAT(pf.Code, ' - ', pt.Code) AS Route,                 
                    FORMAT(IFNULL(fs.LCAdultWkd,0),0) AS LocWkdCost, FORMAT(IFNULL(fs.LCAdultWke,0),0) AS LocWkeCost, 
                    FORMAT(IFNULL(fs.FCAdultWkd,0),0) AS ForWkdCost, FORMAT(IFNULL(fs.FCAdultWke,0),0)  AS ForWkeCost,
                    FORMAT(IFNULL(fs.L1WAdultWkd,0),0) AS LocWkdSales, FORMAT(IFNULL(fs.L1WAdultWke,0),0) AS LocWkeSales, 
                    FORMAT(IFNULL(fs.F1WAdultWkd,0),0) AS ForWkdSales, FORMAT(IFNULL(fs.F1WAdultWke,0),0)  AS ForWkeSales
                    FROM ferferrypricing fs
                    LEFT OUTER JOIN ferbusinesspartnerport bpp ON fs.BusinessPartnerPort = bpp.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON bpp.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN ferroute r ON fs.Route = r.Oid
                    LEFT OUTER JOIN ferport pf ON r.PortFrom = pf.Oid
                    LEFT OUTER JOIN ferport pt ON r.PortTo = pt.Oid
                    WHERE fs.Period = '{$request->Period}'
                    AND bp.Oid = '{$request->BusinessPartner}'
                    {$queryRoute}
                    ORDER BY bp.Code, pf.Code, pt.Code";
                $data = DB::select($query);

                return response()->json(
                    $data, Response::HTTP_CREATED
                );
            }
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function updateProcess(Request $request) {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $dataArray = object_to_array($request);
        if ($request->Period != null) throw new UserFriendlyException("Period is required");
        if ($request->BusinessPartner != null) throw new UserFriendlyException("BusinessPartner is required");

        try {            
            $queryRoute = "";
            $query = "";
            if ($request->Route != null) $queryRoute = "AND fs.Route = '".$request->Route."'";
            
            //UPDATE HARGA DARI PARAMETER
            $query = "UPDATE ferferrypricing fs
                    LEFT OUTER JOIN ferbusinesspartnerport bpp ON fs.BusinessPartnerPort = bpp.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON bpp.BusinessPartner = bp.Oid
                SET
                    L1WAdultWkd= {$request->WeekdayLocal},
                    L1WChildWkd= {$request->WeekdayLocal},
                    L2WAdultWkd= {$request->WeekdayLocal},
                    L2WChildWkd= {$request->WeekdayLocal},
                    F1WAdultWkd= {$request->WeekdayForeigner},
                    F1WChildWkd= {$request->WeekdayForeigner},
                    F2WAdultWkd= {$request->WeekdayForeigner},
                    F2WChildWkd= {$request->WeekdayForeigner},
                    L1WAdultWke= {$request->WeekendLocal},
                    L1WChildWke= {$request->WeekendLocal},
                    L2WAdultWke= {$request->WeekendLocal},
                    L2WChildWke= {$request->WeekendLocal},
                    F1WAdultWke= {$request->WeekendForeigner},
                    F1WChildWke= {$request->WeekendForeigner},
                    F2WAdultWke= {$request->WeekendForeigner},
                    F2WChildWke= {$request->WeekendForeigner},
                    LCAdultWkd= {$request->WeekdayLocalCost},
                    LCChildWkd= {$request->WeekdayLocalCost},
                    FCAdultWkd= {$request->WeekdayForeignerCost},
                    FCChildWkd= {$request->WeekdayForeignerCost},
                    LCAdultWke= {$request->WeekendLocalCost},
                    LCChildWke= {$request->WeekendLocalCost},
                    FCAdultWke= {$request->WeekendForeignerCost},
                    FCChildWke= {$request->WeekendForeignerCost}

                WHERE fs.Period = '{$request->period}'
                AND bp.Oid = '{$request->BusinessPartner}'
                {$queryRoute}";
            DB::update($query);
            return response()->json(
                null, Response::HTTP_NO_CONTENT
            );
            
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function searchQty(Request $request) {
        try {
              
            $criteria = '';
            if (!empty($request->query('time'))) $criteria =" AND fs.Time = '{$request->query('time')}'";

            $query = "SELECT fs.Time AS Time,               
                Day1 AS `1`, Day2 AS `2`, Day3 AS `3`, Day4 AS `4`, Day5 AS `5`, Day6 AS `6`, Day7 AS `7`, Day8 AS `8`, Day9 AS `9`, Day10 AS `10`, 
                Day11 AS `11`, Day12 AS `12`, Day13 AS `13`, Day14 AS `14`, Day15 AS `15`, Day16 AS `16`, Day17 AS `17`, Day18 AS `18`, Day19 AS `19`, Day20 AS `20`, 
                Day21 AS `21`, Day22 AS `22`, Day23 AS `23`, Day24 AS `24`, Day25 AS `25`, Day26 AS `26`, Day27 AS `27`, Day28 AS `28`, Day29 AS `29`, Day30 AS `30`, Day30 AS `31`
                FROM ferferryschedule fs
                LEFT OUTER JOIN ferbusinesspartnerport bpp ON fs.BusinessPartnerPort = bpp.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON bpp.BusinessPartner = bp.Oid
                LEFT OUTER JOIN ferroute r ON fs.Route = r.Oid
                LEFT OUTER JOIN ferport pf ON r.PortFrom = pf.Oid
                LEFT OUTER JOIN ferport pt ON r.PortTo = pt.Oid
                WHERE fs.Period = '{$request->input('period')}'
                AND bp.Oid = '{$request->input('businesspartner')}'
                AND r.Oid = '{$request->input('route')}' 
                {$criteria}
                ORDER BY bp.Code, pf.Code, pt.Code, fs.Time";
            $data = DB::select($query);
    
            return response()->json(
                $data, Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function updateQty(Request $request) {
        $input = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF

        try {
            if ($request->period == null) throw new UserFriendlyException("Period is required");
            if ($request->businesspartner == null) throw new UserFriendlyException("BusinessPartner is required");
            if ($request->route == null) throw new UserFriendlyException("Route is required");

            if ($input->DayFrom == 0) $dayfrom = 1; else $dayfrom = $input->DayFrom;
            if ($input->DayTo == 0) $dayto = 31; else $dayto= $input->DayTo;
            

            $criteria = '';
            if (!empty($request->query('time'))) $criteria =" AND fs.Time = '{$request->query('time')}'";

            $select = "";

            for ($i = $dayfrom; $i <= $dayto; $i++) {
                if (!empty($select)) $select .= ", ";
                $select .= "Day{$i} = $input->Quantity";
            }

            $query = "UPDATE ferferryschedule fs
            LEFT OUTER JOIN ferbusinesspartnerport bpp ON fs.BusinessPartnerPort = bpp.Oid
            LEFT OUTER JOIN mstbusinesspartner bp ON bpp.BusinessPartner = bp.Oid
            LEFT OUTER JOIN ferroute r ON fs.Route = r.Oid
            LEFT OUTER JOIN ferport pf ON r.PortFrom = pf.Oid
            LEFT OUTER JOIN ferport pt ON r.PortTo = pt.Oid
            SET
            {$select}
            WHERE fs.Period = '{$request->input('period')}'
            AND bp.Oid = '{$request->input('businesspartner')}'
            AND r.Oid = '{$request->input('route')}' 
            {$criteria}";
            DB::update($query);
    
            return response()->json(
                null, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
