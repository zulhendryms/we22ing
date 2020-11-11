<?php

namespace App\AdminApi\Master\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Master\Entities\BusinessPartnerAccountGroup;
use App\Core\Master\Resources\BusinessPartnerGroupCollection;
use App\Core\Internal\Entities\BusinessPartnerRole;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class BusinessPartnerGroupController extends Controller
{
    protected $roleService;
    private $crudController;
    private $module;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->module = 'mstbusinesspartnergroup';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        try {
            return $this->crudController->config($this->module);
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
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = BusinessPartnerGroup::whereNull('GCRecord');
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('businesspartneraccountgroup')) $data->where('BusinessPartnerAccountGroup', $request->input('businesspartneraccountgroup'));
            if ($request->has('businesspartneraccountgroupcode')) {
                $businesspartner = $request->input('businesspartneraccountgroupcode');
                $data->whereHas('BusinessPartnerAccountGroupObj', function ($query) use ($businesspartner) {
                    $query->where('Code', $businesspartner);
                });
            }

            if ($request->has('businesspartnerrole')) $data->where('BusinessPartnerRole', $request->input('businesspartnerrole'));
            if ($type != 'combo') $data->with(['BusinessPartnerAccountGroupObj','BusinessPartnerRoleObj']);
            $data = $data->orderBy('Name')->get();
            if($type == 'list'){
                $businesspartners = BusinessPartnerAccountGroup::where('IsActive',1)->whereNull('GCRecord')->orderBy('Name')->get();
                foreach ($businesspartners as $bPartner) {
                    $details = [];
                    foreach ($data as $row) {
                        if ($row->BusinessPartnerAccountGroup == $bPartner->Oid)
                        $details[] = [
                            'Oid'=> $row->Oid,
                            'title' => $row->Name.' '.$row->Code,
                            'expanded' => false,
                        ];
                
                    }
    
                    $results[] = [
                        'Oid'=> $bPartner->Oid,
                        'title' => $bPartner->Name.' '.$bPartner->Code,
                        'expanded' => false,
                        'children' => $details
                    ];
                }

                return $results;
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    private function showSub($Oid)
    {
        $data = BusinessPartnerGroup::whereNull('GCRecord');
        $data = $this->crudController->detail('mstbusinesspartnergroup', $data, $Oid);
        return $data;
    }

    public function show(BusinessPartnerGroup $data)
    {
        $data = $this->crudController->detail($this->module, $data->Oid);
        return $data;
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);
            });

            $role = $this->roleService->list('BusinessPartnerGroup'); //rolepermission
            $data = $this->showSub($data->Oid);
            $data->Action = $this->roleService->generateActionMaster($role);
            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function destroy(BusinessPartnerGroup $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }
}
