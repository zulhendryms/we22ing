<?php

namespace App\AdminApi\Development\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Exceptions\UserFriendlyException;
use App\Core\Master\Entities\CostCenter;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\AdminApi\Development\Controllers\ServerCRUDController;

class CRUDGlobalController extends Controller
{
    protected $roleService;
    private $serverCRUD;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
        $this->serverCRUD = new ServerCRUDController();
    }

    public function config(Request $request, $module)
    {
        try {
            $data = $this->crudController->config($module);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function presearch(Request $request, $module)
    {
        try {
            return $this->crudController->presearch($module);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function dashboard(Request $request, $module)
    {
        try {
            return null;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function list(Request $request, $module)
    {
        try {
            // $tableData = $this->serverCRUD->getDataJSON($module, 'all');
            // $data = DB::table($tableData->Code.' as data');
            // $data = $this->crudController->list($module, $data, $request);
            $data = $this->crudController->list($module, null, $request, true);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function index(Request $request, $module)
    {
        try {
            $type = $request->has('type') ? $request->input('type') : 'combo';
            $tableData = $this->serverCRUD->getDataJSON($module, 'all');
            $data = DB::table($tableData->Code . ' as data');
            if ($request->has('Item')) $data->where('Item', $request->input('Item'));
            $data = $this->crudController->index($module, $data, $request, false);
            if ($type == 'combo') return response()->json($data, Response::HTTP_OK);
            $result = [];
            foreach ($data as $row) $result[] = $this->showSub($module, $row->Oid);
            return response()->json($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function showSub($module, $Oid)
    {
        $data = $this->crudController->detail($module, $Oid);
        return $data;
    }

    public function show($module, $Oid)
    {
        try {
            return $this->showSub($module, $Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function save(Request $request, $module, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $module, $Oid) {
                $data = $this->crudController->saving($module, $request, $Oid, false);
                if (!$data) throw new UserFriendlyException('Data is failed to be saved');
            });

            // $role = $this->roleService->list($module); //rolepermission
            $data = $this->showSub($module, $data->Oid);
            if ($data) $data->Action = $this->roleService->generateActionMaster2();
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

    public function destroy($module, $Oid)
    {
        try {
            $tableData = $this->serverCRUD->getDataJSON($module, 'all');
            $class = config('autonumber.' . $tableData->Code);
            $data = $class::findOrFail($Oid);
            return $this->crudController->delete($module, $data);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
