<?php

namespace App\AdminApi\Trucking\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Trucking\Entities\TruckingTrackingLog;
use App\Core\Security\Services\RoleModuleService;
use Maatwebsite\Excel\Excel;
use App\AdminApi\Trucking\Services\TruckingTrackingLogExcelImport;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class TruckingTrackingLogController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        Excel $excelService,
        RoleModuleService $roleService
    ) {
        $this->excelService = $excelService;
        $this->roleService = $roleService;
        $this->module = 'trctrackinglog';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        try {
            $data =  $this->crudController->config($this->module);
            $data[0]->topButton = [
                [
                'name' => 'Import Excel',
                'type' => 'import_excel',
                'icon' => 'UploadIcon',
                'post' => "truckingtrackinglog/import"
                ]
            ];
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function presearch(Request $request)
    {
        try {
            return $this->crudController->presearch($this->module);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function list(Request $request)
    {
        try {
            $data = DB::table($this->module.' as data');
            $data = $this->crudController->list($this->module, $data, $request,true);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function index(Request $request)
    {
        try {
            $data = DB::table($this->module.' as data');
            $data = $this->crudController->index($this->module, $data, $request, false);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    private function showSub($Oid)
    {
        $data = $this->crudController->detail($this->module, $Oid);
        $data->Action = $this->action($data);
        return $data;
    }

    public function show(TruckingTrackingLog $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);
                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('TruckingTrackingLog'); //rolepermission
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

    public function destroy(TruckingTrackingLog $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), ['file' => 'required|mimes:xls,xlsx']);
        // if ($validator->fails()) return response()->json($validator->messages(), Response::HTTP_UNPROCESSABLE_ENTITY);
        if (!$request->hasFile('file')) return response()->json('No file found', Response::HTTP_UNPROCESSABLE_ENTITY);

        $file = $request->file('file');
        $this->excelService->import(new TruckingTrackingLogExcelImport, $file);
        return response()->json(null, Response::HTTP_CREATED);
    }
}
