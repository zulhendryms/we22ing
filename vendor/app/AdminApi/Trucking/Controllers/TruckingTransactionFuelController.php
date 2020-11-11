<?php

namespace App\AdminApi\Trucking\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Trucking\Entities\TruckingTransactionFuel;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\Core\Internal\Entities\Status;
use App\AdminApi\Pub\Controllers\PublicApprovalController;
use App\AdminApi\Pub\Controllers\PublicPostController;
use App\Core\Base\Services\HttpService;

class TruckingTransactionFuelController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    private $publicApprovalController;
    private $publicPostController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->module = 'trctransactionfuel';
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
        $this->publicApprovalController = new PublicApprovalController();
        $this->publicPostController = new PublicPostController(new RoleModuleService(new HttpService), new HttpService);
    }

    public function config(Request $request)
    {
        try {
            return $this->crudController->config($this->module);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function presearch(Request $request)
    {
        try {
            return $this->crudController->presearch($this->module);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function list(Request $request)
    {
        try {
            $data = DB::table($this->module . ' as data');
            $data = $this->crudController->list($this->module, $data, $request);
            $role = $this->roleService->list('TruckingTransactionFuel'); //rolepermission
            foreach ($data->data as $row) {
                $tmp = TruckingTransactionFuel::where('Oid',$row->Oid)->first();
                if ($tmp) $row->Action = $this->action($tmp);
                $row->Role =  $this->roleService->generateActionMaster($role);
            }
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function index(Request $request)
    {
        try {
            $data = DB::table($this->module . ' as data');
            $data = $this->crudController->index($this->module, $data, $request, true);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    private function showSub($Oid)
    {
        try {
            $data = $this->crudController->detail($this->module, $Oid);
            $data->Action = $this->action($data);
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
            errjson($e);
        }
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);
                $this->publicPostController->sync($data, 'TruckingTransactionFuel');
                if (isset($data->Department) && !in_array($data->StatusObj->Code, ['submit','post','posted','cancel'])) $this->publicApprovalController->formCreate($data, 'TruckingTransactionFuel');
                
            });
            $data = $this->showSub($data->Oid);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function destroy(TruckingTransactionFuel $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function action(TruckingTransactionFuel $data)
    {
        $url = 'truckingtransactionfuel';
        $actionPrintprereport = [
            'name' => 'Print Transaction Fuel',
            'icon' => 'PrinterIcon',
            'type' => 'open_report',
            'hide' => true,
            'get' => 'prereport/transactionfuel?oid={Oid}&report=transactionfuel',
            'afterRequest' => 'init'
        ];
        $actionOpen = [
            'name' => 'Open',
            'icon' => 'ViewIcon',
            'type' => 'edit',
        ];
        $actionEntry = [
            'name' => 'Change to ENTRY',
            'icon' => 'UnlockIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/unpost',
        ];
        $actionPosted = [
            'name' => 'Change to POSTED',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/post',
        ];
        $actionDelete = [
            'name' => 'Delete',
            'icon' => 'TrashIcon',
            'type' => 'confirm',
            'delete' => $url . '/{Oid}'
        ];
        $actionEditCodeNote = [
            "name" => "Edit Code & Note",
            "icon" => "ActivityIcon",
            "type" => "global_form",
            "showModal" => false,
            "get" => "truckingtransactionfuel/{Oid}",
            "post" => "truckingtransactionfuel/{Oid}",
            "afterRequest" => "apply",
            "form" => [
                [
                    'fieldToSave' => "Code",
                    'type' => "inputtext"
                ],
                [
                    'fieldToSave' => "DateProcess",
                    'type' => "inputdate"
                ],
                [
                    'fieldToSave' => "Note",
                    'type' => "inputarea"
                ],
            ]
        ];
        $actionSubmit = $this->publicApprovalController->formAction($data, 'TruckingTransactionFuel', 'submit');
        $actionRequest = $this->publicApprovalController->formAction($data, 'TruckingTransactionFuel', 'request');
        $actionCancel = [
            'name' => 'Cancel',
            'icon' => 'ArrowUpCircleIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/cancel',
            'afterRequest' => 'apply'
        ];
        $return = [];
        switch ($data->Status ? $data->StatusObj->Code : "entry") {
            case "":
                $return[] = $actionPosted;
                $return[] = $actionOpen;
                // $return[] = $actionDelete;
                break;
            case "entry":
                $return[] = $actionRequest;
                $return[] = $actionSubmit;
                // $return[] = $actionDelete;
                $return[] = $actionPosted;
                // $return[] = $actionCancel;
                break;
                case "submit":
                    $return = $this->publicApprovalController->formAction($data, 'TruckingTransactionFuel','approval');
                    $return[] = $actionEntry;
                break;
                case "posted":
                    $return[] = $actionEditCodeNote;
                    $return[] = $actionEntry;
                    $return[] = $actionPrintprereport;
                break;
        }
        return $return;
    }

    public function statusUnpost(TruckingTransactionFuel $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'Entry')->first()->Oid;
                $data->save();

                $this->publicApprovalController->formApprovalReset($data);

            });

            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusPost(TruckingTransactionFuel $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'Posted')->first()->Oid;
                $data->save();
            });

            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
