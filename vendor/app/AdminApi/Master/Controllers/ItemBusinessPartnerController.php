<?php

namespace App\AdminApi\Master\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Base\Services\HttpService;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\ItemBusinessPartner;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\AdminApi\Pub\Controllers\PublicApprovalController;
use App\AdminApi\Pub\Controllers\PublicPostController;

class ItemBusinessPartnerController extends Controller
{
    protected $roleService;
    private $module;
    private $publicApprovalController;
    private $publicPostController;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService,
        HttpService $httpService
    ) {
        $this->module = 'mstitembusinesspartner';
        $this->roleService = $roleService;
        $this->publicApprovalController = new PublicApprovalController();
        $this->publicPostController = new PublicPostController(new RoleModuleService(new HttpService), new HttpService);
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
            $role = $this->roleService->list('ItemBusinessPartner'); //rolepermission
            $data->Role = $this->roleService->generateActionMaster($role);
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
            $role = $this->roleService->list('ItemBusinessPartner'); //rolepermission
            $data->Role = $this->roleService->generateActionMaster($role);
            return $data;
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function show(ItemBusinessPartner $data)
    {
        try {
            return $this->showSub($data->Oid);
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
                $data = $this->crudController->saving($this->module, $request, $Oid, true);
            });
              //PUBLIC POST & APPROVAL
              $this->publicPostController->sync($data, 'ItemBusinessPartner');
              if (isset($data->Department) && in_array($data->StatusObj->Code, ['entry']))
                  $this->publicApprovalController->formCreate($data, 'ItemBusinessPartner');
 
             $role = $this->roleService->list('ItemBusinessPartner'); //rolepermission
             $data = $this->showSub($data->Oid);
             $data->Role = $this->roleService->generateActionMaster($role);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(ItemBusinessPartner $data)
    {
        try {
            return $this->crudController->delete($this->module, $data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
