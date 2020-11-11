<?php

namespace App\AdminApi\Ferry\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Ferry\Entities\FerryPricing;
use App\Core\Ferry\Entities\FerrySchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Security\Services\RoleModuleService;
use Validator;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class FerryPricingController extends Controller
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
        $data = DB::table('ferferrypricing as data')
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
            ->leftJoin('ferbusinesspartnerport AS bpp', 'bpp.Oid', '=', 'data.BusinessPartnerPort')
            ->leftJoin('mstbusinesspartner AS bp', 'bp.Oid', '=', 'bpp.BusinessPartner')
            ->leftJoin('ferroute AS r', 'r.Oid', '=', 'data.Route')
            ->leftJoin('ferport AS pf', 'pf.Oid', '=', 'r.PortFrom')
            ->leftJoin('ferport AS pt', 'pt.Oid', '=', 'r.PortTo')
            ;
        $data = $this->crudController->jsonList($data, $this->fields(), $request, 'ferferrypricing', 'data.Oid');
        $role = $this->roleService->list('FerryPricing'); //rolepermission
        foreach($data as $row) $row->Action = $this->roleService->generateActionMaster($role);
        return $this->crudController->jsonListReturn($data, $this->fields());
    }

    public function index(Request $request)
    {        
        try { 
            $user = Auth::user();
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);           
            $query = "SELECT fp.Oid, bp.Code AS BusinessPartner, CONCAT(pf.Code, ' - ', pt.Code) AS Route
            FROM ferferrypricing fp
            LEFT OUTER JOIN ferbusinesspartnerport bpp ON fp.BusinessPartnerPort = bpp.Oid
            LEFT OUTER JOIN mstbusinesspartner bp ON bpp.BusinessPartner = bp.Oid
            LEFT OUTER JOIN ferroute r ON fp.Route = r.Oid
            LEFT OUTER JOIN ferport pf ON r.PortFrom = pf.Oid
            LEFT OUTER JOIN ferport pt ON r.PortTo = pt.Oid
            WHERE fp.Period = '{$request->input('period')}'
            AND bp.Oid = '{$request->input('businesspartner')}'
            ORDER BY bp.Code, pf.Code, pt.Code";
        $data = DB::select($query);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }
    
    public function show(FerryPricing $data)
    {
        try {    
            $data = FerryPricing::with(['BusinessPartnerPortObj','BusinessPartnerPortObj.BusinessPartnerObj','RouteObj','RouteObj.PortFromObj','RouteObj.PortToObj'])->findOrFail($data->Oid);       
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function edit(Request $request, $Oid = null)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {    
            $query = "UPDATE ferferrypricing fp
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
                WHERE fp.Oid = '{$Oid}'";
            DB::update($query);
            return response()->json(
                null, Response::HTTP_NO_CONTENT
            );
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
        $dataArray = object_to_array($request);
        
        $messsages = array(
            'Code.required'=>__('_.Code').__('error.required'),
            'Code.max'=>__('_.Code').__('error.max'),
            'Name.required'=>__('_.Name').__('error.required'),
            'Name.max'=>__('_.Name').__('error.max'),
            'IsActive.required'=>__('_.IsActive').__('error.required'),
            'City.required'=>__('_.City').__('error.required'),
            'City.exists'=>__('_.City').__('error.exists'),
        );
        $rules = array(
            'Code' => 'required|max:255',
            'Name' => 'required|max:255',           
            'IsActive' => 'required',    
            'City' => 'required|exists:mstcity,Oid',       
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {            
            if (!$Oid) $data = new FerryPricing();
            else $data = FerryPricing::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                if ($request->Code == '<<Auto>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
                $disabled = disabledFieldsForEdit();
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();
                if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'ferferrypricing');
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('FerryPricing'); //rolepermission
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

    public function destroy(FerryPricing $data)
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

    
    public function updateSearch(Request $request) {
        try {
            // $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF         
            
            $fs = FerrySchedule::where('Period',$request->input('Period'))->first();
            if ($fs != null)
            {
                //MASUKIN SEMUA PRICING DARI BULAN TERAKHIR
                $fps = FerryPricing::where('Period','<=',$request->input('Period'))->orderBy('Period','DESC')->get();
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
                        UUID(),fps.Company,fps.BusinessPartner,fps.BusinessPartnerPort,'{$request->input('Period')}',fps.Route,fps.Currency,
                        L1WAdultWkd,L1WChildWkd,L2WAdultWkd,L2WChildWkd,
                        F1WAdultWkd,F1WChildWkd,F2WAdultWkd,F2WChildWkd,
                        L1WAdultWke,L1WChildWke,L2WAdultWke,L2WChildWke,
                        F1WAdultWke,F1WChildWke,F2WAdultWke,F2WChildWke,
                        LCAdultWkd,LCChildWkd,FCAdultWkd,FCChildWkd,LCAdultWke,LCChildWke,FCAdultWke,FCChildWke
                    FROM ferferrypricing fps
                    WHERE 
                        NOT EXISTS (SELECT * FROM ferferrypricing fp WHERE fp.BusinessPartnerPort = fps.BusinessPartnerPort AND fp.Route = fps.Route AND Period = '{$request->input('Period')}') 
                        AND fps.Period = '{$fp->Period}' AND fps.BusinessPartner = '{$request->input('Businesspartner')}'
                    GROUP BY fps.BusinessPartner, fps.BusinessPartnerPort";
                     logger($query);
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
                        UUID(),fs.Company,bpp.BusinessPartner,fs.BusinessPartnerPort,'{$request->input('Period')}',fs.Route,fs.Currency,
                        Local1WAdult,Local1WChild,Local2WAdult,Local2WChild,
                        Foreigner1WAdult,Foreigner1WChild,Foreigner2WAdult,Foreigner2WChild,
                        Local1WAdult,Local1WChild,Local2WAdult,Local2WChild,
                        Foreigner1WAdult,Foreigner1WChild,Foreigner2WAdult,Foreigner2WChild
                    FROM ferferryschedule fs
                        LEFT OUTER JOIN ferbusinesspartnerport bpp ON fs.BusinessPartnerPort = bpp.Oid
                    WHERE 
                        NOT EXISTS (SELECT * FROM ferferrypricing fp WHERE fp.BusinessPartnerPort = fs.BusinessPartnerPort AND fp.Route = fs.Route AND Period = '{$request->input('Period')}') 
                        AND fs.Period = '{$request->input('Period')}' AND bpp.BusinessPartner = '{$request->input('Businesspartner')}'
                    GROUP BY bpp.BusinessPartner, fs.BusinessPartnerPort;";
                    DB::insert($query);
                }
            }

            $queryRoute = "";
            if ($request->input('Route') != null) $queryRoute = "AND fs.Route = '".$request->input('Route')."'";
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
                WHERE fs.Period = '{$request->input('Period')}'
                AND bp.Oid = '{$request->input('Businesspartner')}'
                {$queryRoute}
                ORDER BY bp.Code, pf.Code, pt.Code";
            $data = DB::select($query);

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
    
    public function updateProcess(Request $request) {
        $input = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
       
        if ($request->input('Period') != null) 
        if ($request->input('Businesspartner') != null)

        try {            
            $queryRoute = "";
            $query = "";
            if ($request->input('Route') != null) $queryRoute = "AND fs.Route = '".$request->input('Route')."'";
            
            //UPDATE HARGA DARI PARAMETER
            $query = "UPDATE ferferrypricing fs
                    LEFT OUTER JOIN ferbusinesspartnerport bpp ON fs.BusinessPartnerPort = bpp.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON bpp.BusinessPartner = bp.Oid
                SET
                    L1WAdultWkd= {$input->WeekdayLocal},
                    L1WChildWkd= {$input->WeekdayLocal},
                    L2WAdultWkd= {$input->WeekdayLocal},
                    L2WChildWkd= {$input->WeekdayLocal},
                    F1WAdultWkd= {$input->WeekdayForeigner},
                    F1WChildWkd= {$input->WeekdayForeigner},
                    F2WAdultWkd= {$input->WeekdayForeigner},
                    F2WChildWkd= {$input->WeekdayForeigner},
                    L1WAdultWke= {$input->WeekendLocal},
                    L1WChildWke= {$input->WeekendLocal},
                    L2WAdultWke= {$input->WeekendLocal},
                    L2WChildWke= {$input->WeekendLocal},
                    F1WAdultWke= {$input->WeekendForeigner},
                    F1WChildWke= {$input->WeekendForeigner},
                    F2WAdultWke= {$input->WeekendForeigner},
                    F2WChildWke= {$input->WeekendForeigner},
                    LCAdultWkd= {$input->WeekdayLocalCost},
                    LCChildWkd= {$input->WeekdayLocalCost},
                    FCAdultWkd= {$input->WeekdayForeignerCost},
                    FCChildWkd= {$input->WeekdayForeignerCost},
                    LCAdultWke= {$input->WeekendLocalCost},
                    LCChildWke= {$input->WeekendLocalCost},
                    FCAdultWke= {$input->WeekendForeignerCost},
                    FCChildWke= {$input->WeekendForeignerCost}

                WHERE fs.Period = '{$request->input('Period')}'
                AND bp.Oid = '{$request->input('Businesspartner')}'
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
}
