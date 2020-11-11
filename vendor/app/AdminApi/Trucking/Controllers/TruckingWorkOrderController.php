<?php

namespace App\AdminApi\Trucking\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Trucking\Entities\TruckingWorkOrder;
use App\Core\Trucking\Entities\TruckingWorkOrderLog;
use App\Core\Trucking\Entities\TruckingWorkOrderImage;
use App\Core\Trucking\Entities\TruckingAddress;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Pub\Entities\PublicComment;
use App\Core\Pub\Entities\PublicApproval;
use App\Core\Pub\Entities\PublicFile;
use App\Core\Master\Entities\Image;
use App\Core\Base\Services\HttpService;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class TruckingWorkOrderController extends Controller
{
    private $httpService;
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService,
        HttpService $httpService
    ) {
        $this->httpService = $httpService;
        $this->roleService = $roleService;
        $this->module = 'trcworkorder';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        try {
            return $this->crudController->config($this->module);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function presearch(Request $request)
    {
        try {
            return $this->crudController->presearch($this->module);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function list(Request $request)
    {
        try {
            $data = DB::table($this->module . ' as data');
            $data = $this->crudController->list($this->module, $data, $request);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function index(Request $request)
    {
        try {
            $data = DB::table($this->module . ' as data');
            $data = $this->crudController->index($this->module, $data, $request, false);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function showSub($Oid)
    {
        try {
            $data = $this->crudController->detail($this->module, $Oid);
            return $data;
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function show($data)
    {
        try {
            return $this->showSub($data);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);

                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
                $fromAddressNew = isset($request->FromAddressNew) ? $request->FromAddressNew : false;
                if ($fromAddressNew) {
                    $tmp = TruckingAddress::where('Name', $request->FromAddressName)->first();
                    if (!$tmp) {
                        $tmp = new TruckingAddress();
                        $tmp->Code = now()->format('mdHis') . str_random(2);
                        $tmp->Name = $request->FromAddressName;
                        $tmp->BusinessPartner = $request->FromAddressCompany;
                        $tmp->Address = $request->FromAddressDescription;
                        $tmp->City = $request->FromAddressCity;
                        $tmp->save();
                    }
                    $request->FromAddress = $tmp->Oid;
                }

                $toAddressNew = isset($request->ToAddressNew) ? $request->ToAddressNew : false;
                if ($toAddressNew) {
                    $tmp = TruckingAddress::where('Name', $request->ToAddressName)->first();
                    if (!$tmp) {
                        $tmp = new TruckingAddress();
                        $tmp->Code = now()->format('mdHis') . str_random(2);
                        $tmp->Name = $request->ToAddressName;
                        $tmp->BusinessPartner = $request->ToAddressCompany;
                        $tmp->Address = $request->ToAddressDescription;
                        $tmp->City = $request->ToAddressCity;
                        $tmp->save();
                    }
                    $request->ToAddress = $tmp->Oid;
                }
                if ($data->ContentType == '0') $data->ContentType = 0;
                if ($data->WorkType == '0') $data->WorkType = 0;
                if ($data->ContainerNumberIsDriverFill == '0') $data->ContainerNumberIsDriverFill = 0;
                // $data->ContentType = 1 ? 'Laden' : 'Empty';
                if (!$data->TruckingStatus) $data->TruckingStatus = 'Entry';
                if (!$data->Date) $data->Date = now()->addHours(company_timezone())->toDateTimeString();
                if (!$data->DueDate) $data->DueDate = DATE_ADD(now(), date_interval_create_from_date_string('4 days'));
                $data->save();
                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('TruckingWorkOrder'); //rolepermission
            $data = $this->showSub($data->Oid);
            $data->Action = $this->roleService->generateActionMaster($role);
            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(TruckingWorkOrder $data)
    {
        try {
            DB::transaction(function () use ($data) {
                //delete
                $delete = PublicApproval::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = Image::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicComment::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicFile::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicPost::where('Oid', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = TruckingWorkOrderLog::where('TruckingWorkOrder', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = TruckingWorkOrderImage::where('TruckingWorkOrder', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $data->delete();
            });
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function listController(Request $request)
    {
        $field = "";
        // if ($request->input('status') == 'Assigned') $field = "";
        if ($request->input('status') == 'Assigned') $field = ", IFNULL(driver.Name, driver.UserName) UserDriverName";
        if ($request->input('status') == 'Verify') $field = ", IFNULL(driver.Name, driver.UserName) UserDriverName";
        if ($request->input('status') == 'Unbilled') $field = ", IFNULL(driver.Name, driver.UserName) UserDriverName, wo.DueDate DueDate";

        //sort
        $sort = "";
        if ($request->input('sort') == 'FromBusinessPartnerName') $sort = "bp.Code";
        if ($request->input('sort') == 'FromAddressName') $sort = "fa.Name";
        if ($request->input('sort') == 'ContainerNumber') $sort = "wo.ContainerNumber";
        if ($request->input('sort') == 'ToAddressName') $sort = "ta.Name";
        if ($request->input('sort') == 'FromAddressName') $sort = "fa.Name";
        if ($request->input('sort') == 'TruckingStatus') $sort = "wo.TruckingStatus";
        if ($request->input('sort') == 'DriverName') $sort = "driver.Name";
        if ($request->input('sort') == 'DueDate') $sort = "wo.DueDate";
        if (!$sort) $sort = "CreatedAt";

        $status = "";
        if ($request->input('businesspartner')) $status = "AND wo.BusinessPartner ='{$request->input('businesspartner')}'";
        if ($request->input('containernumber')) $status = "AND wo.ContainerNumber ='{$request->input('containernumber')}'";
        if ($request->input('truckingstatus')) $status = "AND wo.TruckingStatus ='{$request->input('truckingstatus')}'";
        if ($request->input('driver')) $status = "AND driver.Oid ='{$request->input('driver')}'";
        if ($request->input('duedate')) $status = "AND wo.DueDate ='{$request->input('duedate')}'";
        if ($request->input('fromaddress')) $status = "AND fa.Oid ='{$request->input('fromaddress')}'";
        if ($request->input('toaddress')) $status = "AND ta.Oid ='{$request->input('toaddress')}'";
        if ($request->input('status') == 'Unassigned') $status = "AND TruckingStatus IN ('Entry')";
        if ($request->input('status') == 'Assigned') $status = "AND TruckingStatus IN ('Assigned','Started')";
        if ($request->input('status') == 'Verify') $status = "AND TruckingStatus IN ('Ended')";
        if ($request->input('status') == 'Unbilled') $status = "AND TruckingStatus IN ('Vberify')";

        // LEFT OUTER JOIN (
        //     SELECT wolog.TruckingWorkorder, MAX(wolog.CreatedAt) CreatedAt
        //     FROM trcworkorderlog wolog 
        //     WHERE wolog.TruckingStatus = 'Started'
        //     GROUP BY wolog.TruckingStatus, wolog.TruckingWorkorder
        // ) wolog ON wolog.TruckingWorkorder = wo.Oid
        $query = "SELECT 
                wo.Oid, wo.Code, fbp.Code FromBusinessPartnerName, bp.Code BusinessPartnerName, 
                rou.Code TruckingRouteName,
                CONCAT(IFNULL(ct.Code,'20'), (CASE WHEN IFNULL(wo.CargoType,FALSE) THEN ' LD ' ELSE' MT ' END), IFNULL(wo.ContainerNumber,'')) ContainerNumber, 
                wo.CreatedAt, wo.TruckingStatus " . $field . "
            FROM trcworkorder wo 
                LEFT OUTER JOIN mstbusinesspartner fbp ON fbp.Oid = wo.FromBusinessPartner
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = wo.BusinessPartner
                LEFT OUTER JOIN trccontainertype ct ON wo.ContainerTypeAndSize = ct.Oid
                LEFT OUTER JOIN trcaddress fa ON wo.FromAddress = fa.Oid
                LEFT OUTER JOIN trcaddress ta ON wo.FromAddress = ta.Oid
                LEFT OUTER JOIN trcroute rou ON rou.Oid = wo.TruckingRoute
                LEFT OUTER JOIN user driver ON driver.Oid = wo.UserDriver
            WHERE wo.Company IS NOT NULL AND wo.GCRecord IS NULL AND TruckingStatus != 'Cancelled' " . $status . "
            ORDER BY " . $sort . "";
        return DB::select($query);
    }

    public function action(TruckingWorkOrder $data)
    {
        $url = 'truckingworkorder';
        $actionDriverAssign = [
            'name' => 'Assign Driver',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'post' => $url . '/driver',
        ];
        $actionStatusStarted = [
            'name' => 'Started',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'post' => $url . '/start',
        ];
        $actionStatusEnded = [
            'name' => 'Ended',
            'icon' => 'XIcon',
            'type' => 'confirm',
            'post' => $url . '/end',
        ];
        $actionStatusVerify = [
            'name' => 'Verify',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'get' =>  $url . '/verify',
        ];
        $actionStatusCompleted = [
            'name' => 'Completed',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'get' =>  $url . '/completed',
        ];
        $actionViewJournal = [
            'name' => 'View Journal',
            'icon' => 'BookOpenIcon',
            'type' => 'open_grid',
            'get' => 'journal?' . $url . '={Oid}',
        ];
        $actionViewStock = [
            'name' => 'View Stock',
            'icon' => 'PackageIcon',
            'type' => 'open_grid',
            'get' => 'stock?' . $url . '={Oid}',
        ];
        $return = [];
        switch ($data->StatusObj->Code) {
            case "":
                $return[] = $actionStatusStarted;
                break;
            case "entry":
                $return[] = $actionStatusStarted;
                break;
            case "started":
                $return[] = $actionDriverAssign;
                $return[] = $actionStatusEnded;
                break;
            case "assigned":
                $return[] = $actionStatusVerify;
                $return[] = $actionStatusCompleted;
                break;
            case "completed":
                $return[] = $actionViewJournal;
                $return[] = $actionViewStock;
                break;
        }
        return $return;
    }

    public function driverAssign(Request $request)
    {
        $data = TruckingWorkOrder::findOrFail($request->input('oid'));
        // $data->UserDriver = $request->input('driver');
        $data->TruckingStatus = 'Assigned';
        $data->UserDriver = $request->UserDriver;
        $data->FromAddress = $request->FromAddress;
        $data->ToAddress = $request->ToAddress;
        $data->TruckingDriverCode = $request->TruckingDriverCode;
        $data->save();
        $this->createLog($data, 'Driver assigned to ' . $data->UserDriverObj->Name);
        $data = $this->showSub($data->Oid);
        return $data;
    }

    public function driverReassign(Request $request)
    {
        $data = TruckingWorkOrder::findOrFail($request->input('oid'));
        // $data->UserDriver = $request->input('driver');        
        $data->TruckingStatus = 'Entry';
        $data->UserDriver = null;
        $data->save();
        $this->createLog($data, 'Driver cancel assign');
        $data = $this->showSub($data->Oid);
        return $data;
    }

    public function listlastposition(Request $request)
    {
        switch ($request->input('type')) {
            case 'userdriver':
                $query = "SELECT u.Oid, u.UserName, latest.CreatedAt, addr.Name
                FROM user u 
                LEFT OUTER JOIN 
                ( SELECT UserDriver, MAX(CreatedAt) AS CreatedAt
                  FROM trcworkorderlog GROUP BY UserDriver) AS latest ON latest.UserDriver = u.Oid
                LEFT OUTER JOIN trcworkorderlog log ON log.CreatedAt = latest.CreatedAt AND log.UserDriver = u.Oid
                LEFT OUTER JOIN trcworkorder wo ON wo.Oid = log.TruckingWorkOrder AND wo.UserDriver = u.Oid
                LEFT OUTER JOIN trcaddress addr ON addr.Oid = wo.ToAddress";
                break;
            case 'trailer':
                $query = "SELECT t.Oid, t.Code, addr.Name, lastest.CreatedAt
                FROM trctrailer t 
                LEFT OUTER JOIN (SELECT TruckingTrailer, MAX(CreatedAt) AS CreatedAt
                FROM trcworkorderlog log GROUP BY TruckingTrailer) AS lastest ON lastest.TruckingTrailer = t.Oid
                LEFT OUTER JOIN trcworkorderlog log ON log.CreatedAt = lastest.CreatedAt AND log.TruckingTrailer = t.Oid
                LEFT OUTER JOIN trcworkorder wo ON wo.Oid = log.TruckingWorkOrder AND wo.TruckingTrailer = t.Oid
                LEFT OUTER JOIN trcaddress addr ON addr.Oid = wo.ToAddress";
                break;
            case 'primemover':
                $query = "SELECT pm.Oid, pm.Code, addr.Name, lastest.CreatedAt
                FROM trcprimemover pm 
                LEFT OUTER JOIN (SELECT TruckingPrimeMover, MAX(CreatedAt) AS CreatedAt
                FROM trcworkorderlog log GROUP BY TruckingPrimeMover) AS lastest ON lastest.TruckingPrimeMover = pm.Oid
                LEFT OUTER JOIN trcworkorderlog log ON log.CreatedAt = lastest.CreatedAt AND log.TruckingPrimeMover = pm.Oid
                LEFT OUTER JOIN trcworkorder wo ON wo.Oid = log.TruckingWorkOrder AND wo.TruckingPrimeMover = pm.Oid
                LEFT OUTER JOIN trcaddress addr ON addr.Oid = wo.ToAddress";
                break;
        }
        $data = DB::select($query);
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }


    private function createLog(TruckingWorkOrder $data, $description)
    {
        $user = Auth::user();
        $detail = new TruckingWorkOrderLog();
        $detail->TruckingWorkOrder = $data->Oid;
        $detail->User = $user->Oid;
        $detail->TruckingStatus = $data->TruckingStatus;
        $detail->Description = $description;
        $detail->UserDriver = $data->UserDriver;
        $detail->TruckingPrimeMover = $data->TruckingPrimeMover;
        $detail->TruckingTrailer = $data->TruckingTrailer;
        $detail->DueDate = $data->DueDate;

        $detail->Longitude = null;
        $detail->Latitude = null;
        $detail->save();
        return $detail;
    }

    public function statusStarted(Request $request)
    {
        $user = Auth::user();
        $oid = $request->input('oid');
        $data = TruckingWorkOrder::findOrFail($oid);
        $data->TruckingStatus = 'Started';
        // if (!$data->SealNumber) throw new \Exception("SealNumber is empty");
        // if (!$data->TrailerNumber) throw new \Exception("TrailerNumber is empty");
        // if (!$data->ContainerNumber) throw new \Exception("ContainerNumber is empty");
        $data->save();
        $this->createLog($data, 'Driver start to work (Backend)');

        return response()->json(
            $data,
            Response::HTTP_CREATED
        );
    }

    public function statusEnded(Request $request)
    {
        $user = Auth::user();
        $oid = $request->input('oid');
        $data = TruckingWorkOrder::findOrFail($oid);
        $data->TruckingStatus = 'Ended';
        if (isset($request->ArrivedContact)) $data->ArrivedContact = $request->ArrivedContact;
        if (isset($request->ArrivedRemark)) $data->ArrivedRemark = $request->ArrivedRemark;
        $data->save();
        $this->createLog($data, 'Driver finish job (Backend)');

        $data->UserDriverObj->TruckingWorkOrder = $oid;
        $data->UserDriverObj->save();
        // $data->TruckingTrailerObj->TruckingWorkOrder = $oid;
        // $data->TruckingTrailerObj->save();
        // $data->TruckingPrimeMoverObj->TruckingWorkOrder = $oid;
        // $data->TruckingPrimeMoverObj->save();

        return response()->json(
            $data,
            Response::HTTP_CREATED
        );
    }

    public function statusVerify(Request $request)
    {
        $user = Auth::user();
        $oid = $request->input('oid');
        $data = TruckingWorkOrder::findOrFail($oid);
        $data->TruckingStatus = 'Verify';
        // if (!$data->SealNumber) throw new \Exception("SealNumber is empty");
        // if (!$data->TrailerNumber) throw new \Exception("TrailerNumber is empty");
        // if (!$data->ContainerNumber) throw new \Exception("ContainerNumber is empty");
        $data->save();
        $this->createLog($data, 'Transaction Is Verified (Backend)');

        return response()->json(
            $data,
            Response::HTTP_CREATED
        );
    }

    public function statusCompleted(Request $request)
    {
        $user = Auth::user();
        $oid = $request->input('oid');
        $data = TruckingWorkOrder::findOrFail($oid);
        $data->TruckingStatus = 'Completed';
        // if (!$data->SealNumber) throw new \Exception("SealNumber is empty");
        // if (!$data->TrailerNumber) throw new \Exception("TrailerNumber is empty");
        // if (!$data->ContainerNumber) throw new \Exception("ContainerNumber is empty");
        $data->save();
        $this->createLog($data, 'Transaction Is Completed (Backend)');

        return response()->json(
            $data,
            Response::HTTP_CREATED
        );
    }

    public function statusReject(Request $request)
    {
        $user = Auth::user();
        $oid = $request->input('oid');
        $data = TruckingWorkOrder::findOrFail($oid);
        $data->TruckingStatus = 'Rejected';
        // if (!$data->SealNumber) throw new \Exception("SealNumber is empty");
        // if (!$data->TrailerNumber) throw new \Exception("TrailerNumber is empty");
        // if (!$data->ContainerNumber) throw new \Exception("ContainerNumber is empty");
        $data->save();
        $this->createLog($data, 'Transaction Is Completed (Backend)');

        return response()->json(
            $data,
            Response::HTTP_CREATED
        );
    }

    private function createSub($request, $containerNumber = null)
    {
        $data = new TruckingWorkOrder();
        $disabled = array_merge(disabledFieldsForEdit(), [
            'ContainerTypeAndSizeName', 'FromBusinessPartnerName', 'FromPortName', 'FromAddressName', 'ToBusinessPartnerName', 'ToPortName', 'ToAddressName', 'ToBusinessPartner1Name', 'ToPort1Name', 'ToAddress1Name', 'ToBusinessPartner2Name', 'ToPort2Name', 'ToAddress2Name', 'ToBusinessPartner3Name',
            'ToPort3Name', 'ToAddress3Name', 'UserDriverName', 'ContainerNumber', 'UserDriver',
            'TruckingDriverSessionName', 'TruckingPrimeMoverName', 'TruckingTrailerName',
            'BusinessPartnerName', 'Logs', 'Images',
            'FromAddressNew', 'FromAddressName', 'FromAddressCompany', 'FromAddressDescription', 'FromAddressCity', 'FromAddressContact',
            'ToAddressNew', 'ToAddressName', 'ToAddressCompany', 'ToAddressDescription', 'ToAddressCity', 'ToAddressContact',
            'TruckingDriverSessionName', 'TruckingDriverSessionObj', 'TruckingWorkOrderReferenceName', 'TruckingWorkOrderReferenceObj', 'TruckingDriverCodeName', 'TruckingDriverCodeObj', 'TruckingSalesCodeName', 'TruckingSalesCodeObj', 'TruckingRouteName', 'TruckingRouteObj', 'TruckingPortName', 'TruckingPortObj',
        ]);

        $fromAddressNew = isset($request->FromAddressNew) ? $request->FromAddressNew : false;
        if ($fromAddressNew) {
            $tmp = TruckingAddress::where('Name', $request->FromAddressName)->first();
            if (!$tmp) {
                $tmp = new TruckingAddress();
                $tmp->Code = now()->format('mdHis') . str_random(2);
                $tmp->Name = $request->FromAddressName;
                $tmp->BusinessPartner = $request->FromAddressCompany;
                $tmp->Address = $request->FromAddressDescription;
                $tmp->City = $request->FromAddressCity;
                $tmp->save();
            }
            $request->FromAddress = $tmp->Oid;
        }

        $toAddressNew = isset($request->ToAddressNew) ? $request->ToAddressNew : false;
        if ($toAddressNew) {
            $tmp = TruckingAddress::where('Name', $request->ToAddressName)->first();
            if (!$tmp) {
                $tmp = new TruckingAddress();
                $tmp->Code = now()->format('mdHis') . str_random(2);
                $tmp->Name = $request->ToAddressName;
                $tmp->BusinessPartner = $request->ToAddressCompany;
                $tmp->Address = $request->ToAddressDescription;
                $tmp->City = $request->ToAddressCity;
                $tmp->save();
            }
            $request->ToAddress = $tmp->Oid;
        }

        $data = $this->crudController->save('trcworkorder', $data, $request);
        if (!isset($data->TruckingStatus)) $data->TruckingStatus = 'Entry';
        if (!isset($data->Date)) $data->Date = now()->addHours(company_timezone())->toDateTimeString();
        if (!isset($data->DueDate)) $data->DueDate = now()->addDays(4)->toDateTimeString();
        $data->ContainerNumber = $containerNumber;
        $data->ContainerNumberIsDriverFill = false; //isset($containerNumber) ? true : false;
        $data->SealNumberIsDriverFill = false; //isset($data->SealNumber) ? true : false;
        $data->TrailerNumberIsDriverFill = false; //isset($data->TrailerNumber) ? true : false;
        $data->save();
        if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'trcworkorder');
        return $data;
    }

    public function create(Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));

        try {
            $result = [];
            DB::transaction(function () use ($request, &$result) {
                if (!isset($request->ContainerNumber)) {
                    $result[] = $this->createSub($request);
                } else {
                    foreach (preg_split("/((\r?\n)|(\r\n?))/", $request->ContainerNumber) as $line) {
                        $result[] = $this->createSub($request, $line);
                    }
                }
                if (!$result) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('TruckingWorkOrder'); //rolepermission
            $finalresult = [];
            foreach ($result as $row) {
                $tmp = $this->showSub($row->Oid);
                $tmp->Role = $this->roleService->generateActionMaster($role);
                $finalresult[] = $tmp;
            }

            return response()->json(
                $finalresult,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function recreateOrder(Request $request)
    {
        try {
            $workOrder = TruckingWorkOrder::findOrFail($request->input('oid'));
            $data = new TruckingWorkOrder();
            $disabled = array_merge(disabledFieldsForEdit(), [
                'Code', 'FromAddress', 'ToAddress', 'TruckingWorkOrderReference',
                'UserDriver', 'Remark', 'UserDriverSession', 'ArrivedContact', 'ArrivedRemark', 'TruckingPrimeMover', 'TruckingTrailer', 'DueDate', 'Date', 'TruckingDriverCode'
            ]);
            foreach ($workOrder->getAttributes() as $field => $key) {
                if (in_array($field, $disabled)) continue;
                $data->{$field} = $workOrder->{$field};
            }
            $data->Code = $workOrder->Code . '-' . '1';
            $data->TruckingRoute = $request->input('truckingroute');
            $data->ToAddress = $request->input('toaddress');
            if ($request->has('truckingdrivercode')) $data->TruckingDriverCode = $request->input('truckingdrivercode');
            if ($request->has('note')) $data->Note = $request->input('note');
            $data->FromAddress = $workOrder->ToAddress;
            $data->TruckingWorkOrderReference = $workOrder->Oid;
            $data->Date = now()->addHours(company_timezone())->toDateTimeString();
            $data->DueDate = now()->addDays(4)->toDateTimeString();
            $data->ContainerNumberIsDriverFill = false;
            $data->SealNumberIsDriverFill = false;
            $data->TrailerNumberIsDriverFill = false;
            $data->TruckingStatus = 'Entry';
            $data->save();

            $workOrder->TruckingStatus = 'Completed';
            $workOrder->save();

            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
