<?php

namespace App\AdminApi\Pub\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Pub\Entities\PublicPostLike;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Services\HttpService;
use App\Core\Base\Exceptions\UserFriendlyException;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class PublicPostController extends Controller
{
    private $httpService;
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(RoleModuleService $roleService, HttpService $httpService)
    {
        $this->roleService = $roleService;
        $this->httpService = $httpService;
        $this->httpService->baseUrl('http://api1.ezbooking.co:888')->json();
        $this->module = 'pubpost';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        $fields = $this->httpService->get('/portal/api/development/table/vuelist?code=PublicPost');
        foreach ($fields as &$row) { //combosource
            if ($row->headerName  == 'Company') $row->source = comboselect('company');
        };
        return $fields;
    }

    public function list(Request $request)
    {
        $fields = $this->httpService->get('/portal/api/development/table/vuelist?code=PublicPost');
        $data = DB::table('pubpost as data') //jointable
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company');
        $data = $this->crudController->jsonList($data, $this->fields(), $request, 'pubpost', 'Name');
        $role = $this->roleService->list('PublicPost'); //rolepermission
        foreach ($data as $row) $row->Role = $this->roleService->generateRoleMaster($role);
        return $this->crudController->jsonListReturn($data, $fields);
    }


    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = PublicPost::whereNull('GCRecord');

            $data = $data->orderBy('Oid')->get();
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
        $data = PublicPost::with('Likes')->findOrFail($Oid);
        $data->CompanyName = $data->CompanyObj ? $data->CompanyObj->Name : null;
        return $data;
    }

    public function show(PublicPost $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    public function sync($source,$type) {        
        $data = PublicPost::where('Oid',$source->Oid)->first();
        if (!$data) $data = new PublicPost();
        $data->Oid = $source->Oid;
        $data->Company = $source->Company;
        $data->ObjectType = $type;
        $data->ObjectOid = $source->Oid;
        $data->User = $source->CreatedBy;
        $data->Name = $type;
        if (!in_array($type, ['Task'])) {
            $data->Code = $source->Code;
            $data->Date = $source->Date;
            $data->Description = $type.' ' .$source->Code;
            $data->TotalAmount = $source->TotalAmount;
            $data->Status = $source->Status;
            if (isset($source->BusinessPartnerObj)) $data->Note = $data->Note.$source->BusinessPartnerObj->Name.'; ';
            if (isset($source->AccountObj)) $data->Note = $data->Note.$source->AccountObj->Name.'; ';
            // $data->Department = $source->Department;
            // $data->BusinessPartner = $source->BusinessPartner;
        }
        if ($type == 'PurchaseOrder') {
            if ($source->Type == 'PurchaseRequest') {
                $data->Code = $source->RequestCode;
                $data->BusinessPartner = $source->{'Supplier'.$source->SupplierChosen};
                $data->TotalAmount = $source->{'Supplier'.$source->SupplierChosen.'Amount'};
            }
        }
        $data->save();    
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);
                if (!$data) throw new UserFriendlyException('Data is failed to be saved');
            });

            $role = $this->roleService->list('PublicPost'); //rolepermission
            $data = $this->showSub($data->Oid);
            $data->Role = $this->roleService->generateRoleMaster($role);
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

    public function destroy(PublicPost $data)
    {
        try {
            DB::transaction(function () use ($data) {
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
}
