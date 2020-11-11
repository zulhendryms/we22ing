<?php

namespace App\AdminApi\Security\Controllers;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Requests\Api\LoginRequest;
use App\Core\Security\Services\AuthService;
use App\Core\Security\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\Company;
use App\Core\Security\Entities\User;
use App\AdminApi\Master\Controllers\CurrencyController;
use App\AdminApi\System\Controllers\StatusController;
use App\AdminApi\Master\Controllers\ItemTypeController;
use App\AdminApi\System\Controllers\BusinessPartnerRoleController;
use App\AdminApi\Security\Controllers\RoleModuleController;
use App\AdminApi\Master\Controllers\CompanyController;
use App\Core\Internal\Services\FileCloudService;
use App\Core\Base\Services\HttpService;
use App\Core\Master\Entities\Currency;
use App\Core\Internal\Entities\ItemType;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Services\AutoNumberService;
use Illuminate\Support\Facades\DB;
use App\Core\Internal\Entities\AutoNumber;
use App\Core\Internal\Entities\AutoNumberSetup;

class LoginController extends Controller {
    
    private $authService; 
    private $userService; 
    private $httpService;
    protected $roleService;
    public function __construct(AuthService $authService, UserService $userService,RoleModuleService $roleService, HttpService $httpService)
    {
        $this->authService = $authService;
        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->httpService = $httpService;
        $this->httpService->baseUrl('http://api1.ezbooking.co:888')->json();
    }
    
    public function index(Request $request)
    {
        return response()->json(
            $this->UserLogin(),
            Response::HTTP_OK
        );       
    }

    public function quickMenu() {
        $user = Auth::user();
        $data = $user->RoleObj ? $user->RoleObj->QuickMenu : null;
        return isJson($data) ? json_decode($data) : $data;
    }

    private function UserLogin() {
        $user = Auth::user();
        // $data = BusinessPartner::with('BusinessPartnerGroupObj.BusinessPartnerRoleObj')->findOrFail($user->BusinessPartner);
        // if ($data->Oid == $user->CompanyObj->BusinessPartner) $user->BusinessPartnerRole = 'Company';
        if (!$user->BusinessPartner) $user->BusinessPartnerRole = 'Company';
        elseif ($user->BusinessPartner == company()->CustomerCash) $user->BusinessPartnerRole = 'Cash';
        else {
            $data = BusinessPartner::with('BusinessPartnerGroupObj.BusinessPartnerRoleObj')->findOrFail($user->BusinessPartner);
            $user->BusinessPartnerRole = $data->BusinessPartnerGroupObj->BusinessPartnerRoleObj->Code;
        }
        $user->BusinessPartnerObj = $user->BusinessPartnerObj;
        return $user;
    }
    public function loginDev(Request $request) {        
        
        $request = object_to_array(json_decode($request->getContent())); //WILLIAM ZEF
        $response = $this->httpService->post('/portal/api/auth/login', [
                "UserName" => $request['UserName'],
                "Password" => $request['Password'],
                "Company" => $request['Company']
        ]);
        return response()->json(
            $response,
            Response::HTTP_OK
        );        
    }
    public function changeCompany(Request $request) {
        try {
            $user = User::where('Oid', Auth::user()->Oid)->first();
            $user->CompanyObj()->associate($request->input('Oid'));
            // $user->Company = $request->input('Oid');
            // $user->save();
            $company = $this->getReturnCompany($user->Company);

            return response()->json(
                $company,
                Response::HTTP_OK
            );        
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function login(Request $request) 
    {
        $request = object_to_array(json_decode($request->getContent())); //WILLIAM ZEF
        $login = $this->authService->login($request, 'api');
        if (!$login) return $login;
        return $login;
    }
    public function logout() 
    {
        return $this->authService->logout($type = 'api');
    }

    public function reset(Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $user = Auth::user();
        $user->ResetCode = now()->format('ymdHis');
        $user->save();
        $this->userService->resetPassword($user->ResetCode,$request->newpassword,$request->oldpassword);
        return response()->json(
            $user,
            Response::HTTP_OK
        );        
    }

private function getReturnCompany($oid) {    
    // $company = new CompanyController(new fileCloudService, new AutoNumberService);
    // $company = $company->list('list');
    $company = Company::with([
        'WarehouseObj' => function ($query) {$query->addSelect('Oid', 'Code', 'Name');},
        'CityObj' => function ($query) {$query->addSelect('Oid', 'Code', 'Name');},
        'CountryObj' => function ($query) {$query->addSelect('Oid', 'Code', 'Name');},
        'CurrencyObj' => function ($query) {$query->addSelect('Oid', 'Code', 'Decimal');},
        'BusinessPartnerObj' => function ($query) {$query->addSelect('Oid', 'Name');},
        'BusinessPartnerAmountDifferenceObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'AccountProfitLossObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'PurchaseDiscountAccountObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'SalesDiscountAccountObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'BusinessPartnerPurchaseDeliveryObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'BusinessPartnerPurchaseInvoiceObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'BusinessPartnerSalesDeliveryObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'BusinessPartnerSalesInvoiceObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'ItemStockObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'ItemAgentObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'ItemSalesIncomeObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'ItemSalesProductionObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'ItemPurchaseExpenseObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'ItemPurchaseProductionObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'IncomeInProgressObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'ExpenseInProgressObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'CashBankExchRateGainObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'CashBankExchRateLossObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'ARAPExchRateGainObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'ARAPExchRateLossObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'AccountIncomeObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'AccountExpenseObj' => function ($query) {$query->addSelect('Oid', 'Name', 'Currency');},
        'ItemAccountGroupObj' => function ($query) {$query->addSelect('Oid', 'Code', 'Name');},
        'BusinessPartnerAccountGroup' => function ($query) {$query->addSelect('Oid', 'Code', 'Name');},
        'POSDefaultWarehouseObj' => function ($query) {$query->addSelect('Oid', 'Code', 'Name');},
        'POSPaymentMethodForChangesObj' => function ($query) {$query->addSelect('Oid', 'Code', 'Name');},
        'POSDefaultTableObj' => function ($query) {$query->addSelect('Oid', 'Code', 'Name');},
        'POSDefaultEmployeeObj' => function ($query) {$query->addSelect('Oid', 'Code', 'Name');},
    ])->where('Oid',$oid)->firstOrFail();
    $logo = $company->Image ?: 'http://public.ezbooking.co/logo/logo_ezb_admin.png';
    $company->Image = $company->Image ?: $logo;
    $company->LogoIcon = $company->LogoIcon ?: $logo;
    $company->LogoLogin = $company->LogoLogin ?: $logo;
    $company->LogoPrint = $company->LogoPrint ?: $logo;
    $company->CompanyObj = [
        'Oid' => $company->Oid,
        'Name' => $company->Code,
    ];
    return $company;
}

    public function initial(Request $request)
    {
        $userLogin = $this->UserLogin();
        $profile = $userLogin->UserProfileObj();
        $userLogin->Image = isset($profile->Image) ? $profile->Image : null;
        $userLogin->Color = $profile->Color;
        // $userLogin->Image = $userLogin->Image ?: 'https://cdn.iconscout.com/icon/free/png-256/account-profile-avatar-man-circle-round-user-30452.png';
        
        $user = Auth::user();
        $company = $this->getReturnCompany($user->Company);
        
        $rolemodule = $this->roleService->roleModule();
        $disabledField = $this->roleService->disablefield();

        // $currency = new CurrencyController();
        // $currency = $currency->list('list');
        $currency = Currency::whereNull('GCRecord')->orderBy('Code')->get();
        // $currency = (new CurrencyCollection($currency))->type('list');

        $statusPOS = new StatusController();
        $statusPOS = $statusPOS->list('ModPOS');

        $statusPurchase = new StatusController();
        $statusPurchase = $statusPurchase->list('ModPurchase');

        $statusAccount = new StatusController();
        $statusAccount = $statusAccount->list('ModAccount');

        $statusSales = new StatusController();
        $statusSales = $statusSales->list('ModSales');

        // $itemtype = new ItemTypeController();
        // $itemtype = $itemtype->list('list');
        $itemtype = ItemType::whereNull('GCRecord')->orderBy('Code')->get();
        // $itemtype = (new ItemTypeCollection($itemtype))->type('list');

        $businesspartnerrole = new BusinessPartnerRoleController();
        $businesspartnerrole = $businesspartnerrole->list('list');
        
        unset($userLogin->CompanyObj);
        if($userLogin->BusinessPartnerRole !== 'Cash'){
            $data = [
                'User' => $userLogin,
                'Company' => $company,
                'RoleModule' => $rolemodule,
                'DisableField' => $disabledField,
                'Currency' => $currency,
                'StatusPOS' => $statusPOS,
                'StatusPurchase' => $statusPurchase,
                'StatusAccount' => $statusAccount,
                'StatusSales' => $statusSales,
                'ItemType' => $itemtype,
                'BusinessPartnerRole' => $businesspartnerrole
            ];
            return $data;
        }else{
            return response()->json('Login Failed', Response::HTTP_NOT_FOUND);
        }      
        
    }
    
}