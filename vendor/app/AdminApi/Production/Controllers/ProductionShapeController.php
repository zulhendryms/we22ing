<?php

namespace App\AdminApi\Production\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Production\Entities\ProductionShape;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class ProductionShapeController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->module = 'prdshape';
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
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
            $data = $this->crudController->list($this->module, $data, $request,true);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function index(Request $request)
    {
        try {
            $data = DB::table($this->module . ' as data');
            $data = $this->crudController->index($this->module, $data, $request, false);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
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

    public function show(ProductionShape $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, true);
            });
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function destroy(ProductionShape $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
            errjson($e);
        }
    }
}
