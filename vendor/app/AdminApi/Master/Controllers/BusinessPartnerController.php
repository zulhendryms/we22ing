<?php

namespace App\AdminApi\Master\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Master\Entities\Company;
use App\Core\Base\Services\HttpService;
use App\Core\Master\Resources\BusinessPartnerResource;
use App\Core\Master\Resources\BusinessPartnerCollection;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Internal\Entities\BusinessPartnerRole;
use App\Core\Master\Entities\BusinessPartnerAccountGroup;
use App\Core\Master\Entities\BusinessPartnerGroupUser;

use Maatwebsite\Excel\Excel;
use App\AdminApi\Master\Services\CustomerExcelImport;
use App\AdminApi\Master\Services\SupplierExcelImport;
use App\AdminApi\Master\Services\BusinessPartnerExcelImport;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class BusinessPartnerController extends Controller
{
    protected $excelService;
    protected $roleService;
    private $crudController;
    private $module;
    public function __construct(
        Excel $excelService,
        RoleModuleService $roleService
    ) {
        $this->module = 'mstbusinesspartner';
        $this->excelService = $excelService;
        $this->roleService = $roleService;
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

    public function list(Request $request)
    {
        try {
            $roletype = "Customer";
            $data = DB::table('mstbusinesspartner as data');
            $user = Auth::user();
            // if ($user->BusinessPartner) $data = $data->where('data.Oid', $user->BusinessPartner);
            // if ($request->has('company')) $data = $data->whereRaw('data.GCRecord IS NULL');
            // else $data = $data->where('data.Oid','!=',$user->CompanyObj->BusinessPartner);
            if ($request->has('role')) {
                $roletype = $request->input('role');
                if ($roletype == 'Supplier') $data->where('BusinessPartnerRole.IsSupplier', true);
                else $data->where('BusinessPartnerRole.IsCustomer', true);
            }

            if ($request->has('businesspartnerrolecode')) {
                $businesspartnerrolecode = $request->input('businesspartnerrolecode');
                if ($businesspartnerrolecode == 'Attraction') $businesspartnerrolecode = "Supplier" . $businesspartnerrolecode;
                if ($businesspartnerrolecode == 'Outbound') $businesspartnerrolecode = "Supplier" . $businesspartnerrolecode;
                if ($businesspartnerrolecode == 'Hotel') $businesspartnerrolecode = "Supplier" . $businesspartnerrolecode;
                if ($businesspartnerrolecode == 'Restaurant') $businesspartnerrolecode = "Supplier" . $businesspartnerrolecode;
                if ($businesspartnerrolecode == 'Transport') $businesspartnerrolecode = "Supplier" . $businesspartnerrolecode;
                $bprole = BusinessPartnerRole::where('Code', $businesspartnerrolecode)->first();
                $roletype = $bprole->IsSupplier ? 'Supplier' : 'Customer';
                $data = $data->where('BusinessPartnerGroup.BusinessPartnerRole', $bprole->Oid);
            }

            if ($roletype == 'Customer') {
                // filter businesspartnergroupuser
                $businessPartnerGroupUser = BusinessPartnerGroupUser::select('BusinessPartnerGroup')->where('User', $user->Oid)->pluck('BusinessPartnerGroup');
                if ($businessPartnerGroupUser->count() > 0) $data->whereIn('data.BusinessPartnerGroup', $businessPartnerGroupUser);
            }
            $data = $this->crudController->list($this->module, $data, $request);
            if ($roletype) $role = $this->roleService->list($roletype); //rolepermission
            else $role = $this->roleService->list('Customer'); //rolepermission
            foreach ($data->data as $row) $row->Action = $this->roleService->generateActionMaster($role);
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
        $data = $this->crudController->detail($this->module, $Oid);
        return $data;
    }

    public function show(BusinessPartner $data)
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
                $data = $this->crudController->saving($this->module, $request, $Oid, false);

                if (!$Oid) {
                    $bpg = BusinessPartnerGroup::findOrFail($data->BusinessPartnerGroup);
                    $company = $data->CompanyObj ?: Auth::user()->CompanyObj;
                    if (!isset($data->NameZH)) $data->NameZH = $data->Name ?: null;
                    if (!isset($data->Initial)) $data->Initial = $data->Name ?: null;
                    if (!isset($data->Slug)) $data->Slug = $data->Name ?: null;
                    if (!isset($data->City)) $data->City = $company->City ?: null;
                    if (!isset($data->IsActive)) $data->IsActive = 1;
                    // if (!isset($data->Description)) $data->DescriptionZH = $data->Description ?: null;
                    if (!isset($data->AgentCurrency)) $data->AgentCurrency = $company->Currency;
                    if (!isset($data->BusinessPartnerRole)) $data->BusinessPartnerRole = $bpg->BusinessPartnerRole ?: null;
                    if (!isset($data->BusinessPartnerAccountGroup)) $data->BusinessPartnerAccountGroup = $bpg->BusinessPartnerAccountGroup ?: null;
                    $bpag = BusinessPartnerAccountGroup::findOrFail($data->BusinessPartnerAccountGroup);
                    if (!isset($data->IsPurchase)) $data->IsPurchase = $bpag->IsPurchase;
                    if (!isset($data->IsSales)) $data->IsSales = $bpag->IsSales;
                    if (!isset($data->PurchaseCurrency)) $data->PurchaseCurrency = $bpag->PurchaseCurrency;
                    if (!isset($data->SalesCurrency)) $data->SalesCurrency = $bpag->SalesCurrency;
                    if (!isset($data->PurchaseTax)) $data->PurchaseTax = $bpag->PurchaseTax;
                    if (!isset($data->PurchaseTerm)) $data->PurchaseTerm = $bpag->PurchaseTerm;
                    if (!isset($data->SalesTax)) $data->SalesTax = $bpag->SalesTax;
                    if (!isset($data->SalesTerm)) $data->SalesTerm = $bpag->SalesTerm;
                    if (!isset($data->RedeemCode)) $data->RedeemCode = mt_rand(100000, 999999);
                }

                $data->BusinessPartnerRole = $data->BusinessPartnerGroupObj->BusinessPartnerRole;
                $data->save();

                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('BusinessPartner'); //rolepermission
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

    public function destroy(BusinessPartner $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function importCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), ['file' => 'required|mimes:xls,xlsx']);

        if ($validator->fails()) return response()->json($validator->messages(), Response::HTTP_UNPROCESSABLE_ENTITY);
        if (!$request->hasFile('file')) return response()->json('No file found', Response::HTTP_UNPROCESSABLE_ENTITY);

        $file = $request->file('file');
        $this->excelService->import(new CustomerExcelImport, $file);
        return response()->json(null, Response::HTTP_CREATED);
    }

    public function importSampleCustomer(Request $request)
    {
        $url = url('importsamples/import_customer.xlsx');
        return response()->json($url, Response::HTTP_OK);
    }

    public function importSupplier(Request $request)
    {
        $validator = Validator::make($request->all(), ['file' => 'required|mimes:xls,xlsx']);

        if ($validator->fails()) return response()->json($validator->messages(), Response::HTTP_UNPROCESSABLE_ENTITY);
        if (!$request->hasFile('file')) return response()->json('No file found', Response::HTTP_UNPROCESSABLE_ENTITY);

        $file = $request->file('file');
        $this->excelService->import(new SupplierExcelImport, $file);
        return response()->json(null, Response::HTTP_CREATED);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), ['file' => 'required|mimes:xls,xlsx']);

        if ($validator->fails()) return response()->json($validator->messages(), Response::HTTP_UNPROCESSABLE_ENTITY);
        if (!$request->hasFile('file')) return response()->json('No file found', Response::HTTP_UNPROCESSABLE_ENTITY);

        $file = $request->file('file');
        $this->excelService->import(new BusinessPartnerExcelImport, $file);
        return response()->json(null, Response::HTTP_CREATED);
    }

    public function importSampleSupplier(Request $request)
    {
        $url = url('importsamples/import_supplier.xlsx');
        return response()->json($url, Response::HTTP_OK);
    }
}
