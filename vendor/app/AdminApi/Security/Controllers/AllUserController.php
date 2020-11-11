<?php

namespace App\AdminApi\Security\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Entities\User;
use Illuminate\Support\Facades\DB;
use App\Core\Security\Services\UserService;
use App\Core\Security\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use App\Core\POS\Entities\POSSession;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class AllUserController extends Controller
{
    private $userService; 
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(AuthService $authService, UserService $userService,RoleModuleService $roleService)
    {
        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->module = 'user';
        $this->crudController = new CRUDDevelopmentController();
    }
    public function fields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = ['w'=> 250, 'n'=>'UserName'];
        $fields[] = serverSideConfigField('Name');
        $fields[] = serverSideConfigField('IsActive');
        return $fields;
    }

    public function config(Request $request) {
        $fields = $this->crudController->jsonConfig($this->fields());
        foreach ($fields as &$row) { //combosource
            // if ($row['headerName'] == 'AccountGroup') $row['source'] = comboSelect('accaccountgroup');
        }
        return $fields;
    }
    public function list(Request $request) {
        $fields = $this->fields();
        $data = DB::table('user as data')
        ;
        $data = $this->crudController->jsonList($data, $this->fields(), $request, 'user');
        $role = $this->roleService->list('User'); //rolepermission
        foreach($data as $row) $row->Action = $this->roleService->generateActionMaster($role);
        return $this->crudController->jsonListReturn($data, $fields);
    }
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = User::whereNull('GCRecord');

            $data = $data->orderBy('Oid')->get();
            $role = $this->roleService->list('User'); //rolepermission
            foreach ($data as $row) $row->Action = $this->roleService->generateActionMaster($role);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function alluser(Request $request)
    {
        $user = Auth::user();
        $type = $request->input('type') ?: 'combo';
        $data = User::whereNull('GCRecord')->whereNull('Deleted');
        if ($user->BusinessPartner) $data = $data->where('BusinessPartner', $user->BusinessPartner);
        $data = $data->orderBy('Name')->get();
        
        $role = $this->roleService->list('User');
        $result = [];
            foreach ($data as $row) {
                $result[] = [
                    'Oid' => $row->Oid,
                    'IsActive' => $row->IsActive,
                    'UserName' => $row->UserName,
                    'Name' => $row->Name,
                    'Code' => $row->Code,
                    'RoleName' => $row->RoleObj ? $row->RoleObj->Name : null,  
                    'Role' => $this->roleService->generateActionMaster($role)
                ];
            }
        return $result;
    }

    private function showSub($Oid)
    {
        $data = User::findOrFail($Oid);

        $data->BusinessPartnerName = $data->BusinessPartnerObj ? $data->BusinessPartnerObj->Name : null;
        $data->CurrencyName = $data->CurrencyObj ? $data->CurrencyObj->Name : null;
        $data->TimezoneName = $data->TimezoneObj ? $data->TimezoneObj->Name : null;
        $data->CountryName = $data->CountryObj ? $data->CountryObj->Name : null;
        $data->InvitorUserName = $data->InvitorUserObj ? $data->InvitorUserObj->Name : null;
        $data->CityName = $data->CityObj ? $data->CityObj->Name : null;

        return $data;
    }

    public function show(User $data)
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

    public function save(Request $request, $Oid = null)
    {        
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        if (!$Oid) $data = new User();
        else $data = User::whereNull('Deleted')->findOrFail($Oid);
        
        DB::transaction(function () use ($request, &$data) {
            $user = Auth::user();
            // $enabled = ['Currency','Lang','Address','Name','PhoneCode','PhoneNo'];
            if (!isset($request->Currency)) $request->Currency = $user->CompanyObj->Currency;
            if (!isset($request->Company)) $request->Company = $user->Company;
            if (!isset($request->IsActive)) $request->IsActive = true;
            if (!isset($request->Name)) $request->Name = $request->UserName;
            $enabled = ['Company','Currency','BusinessPartner','Address','Name','Role','IsActive','UserName'];
            foreach ($request as $field => $key) {
                if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
            }
            if ($data->Oid == null) $data->StoredPassword2 = 'AKmLpVV870RCWse17g6/Ya/8FWqodAaPFwXPW3S1OgrYTBHAEkxHq6nRK+CS5m7tug==';
            $data->save();
            $query = "DELETE FROM userusers_roleroles WHERE Users='".$data->Oid."'";
            DB::delete($query); 
            // $query = "INSERT INTO userusers_roleroles (OID, Users, Roles) VALUES (UUID(), '".$data->Oid."', '".$data->Role."')";
            // DB::insert($query); 
            if(!$data) throw new \Exception('Data is failed to be saved');
        });

        return $data;
        return response()->json(
            $data, Response::HTTP_CREATED
        );
    }

    public function destroy(User $data)
    {
        DB::transaction(function () use ($data) {            
            // $data->GCRecord = now()->format('ymdHi');
            $data->Deleted = now()->format('ymdHi');
            $data->save();
            // $data->delete();
        });
        return response()->json(
            null, Response::HTTP_NO_CONTENT
        );
    }

    public function reset(Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $user = User::findOrFail($request->user);
        $user->ResetCode = now()->format('ymdHis');
        $user->save();
        $this->userService->resetPassword($user->ResetCode,$request->newpassword,$request->oldpassword);
        return response()->json(
            $user,
            Response::HTTP_OK
        );        
    }

    public function favmenu(Request $request)
    {
        $string = "[
            {
              'url': '/currency',
              'name': 'Currency',
              'slug': 'currency',
              'icon': 'DollarSignIcon',
              'i18n': 'Currency'
            },
            {
              'url': '/employeeposition',
              'name': 'Employee Position',
              'slug': 'employeeposition',
              'icon': 'UserCheckIcon',
              'i18n': 'EmployeePosition'
            },
            {
              'url': '/rolemodule',
              'name': 'Role & Permission',
              'slug': 'rolemodule',
              'icon': 'ShieldIcon',
              'i18n': 'RoleModule'
            }
          ]";
          return response()->json(
              $string, Response::HTTP_OK
          );
    }

    
    public function autocomplete(Request $request)
    {
        $type = $request->input('type') ?: 'combo';
        $term = $request->term;
        $user = Auth::user();     

        $data = User::whereNull('GCRecord');
       
        $data->where(function($query) use ($term)
        {
            $query->where('UserName','LIKE','%'.$term.'%')
            ->orWhere('Name','LIKE','%'.$term.'%');
        });
        $data = $data->where('Company',$user->Company)->orderBy('Name')->take(10)->get();
       
        $result = [];
        foreach($data as $row) {
            $businessPartner = $row->BusinessPartner ? $row->BusinessPartner : company()->CustomerCash;
            $businessPartner = BusinessPartner::where('Oid',$businessPartner)->first();
            $currency = $businessPartner ? $businessPartner->SalesCurrencyObj : company()->CurrencyObj;
            if ($businessPartner) {
                $businessPartner = [
                    'Oid' => $businessPartner->Oid,
                    'Name' => $businessPartner->Name.' - '.$businessPartner->Code,
                ];
                $bpag = isset($businessPartner->BusinessPartnerAccountGroupObj) ? $businessPartner->BusinessPartnerAccountGroupObj->SalesInvoiceObj : company()->BusinessPartnerSalesInvoiceObj;
                $account = [
                    'Oid' => $bpag ? $bpag->Oid : null,
                    'Name' => $bpag ? $bpag->Name.' - '.$bpag->Code : null,
                ];
            } else {  
                $businessPartner = [
                    'Oid' => null,
                    'Name' => null,
                ];
                $account = [
                    'Oid' => null,
                    'Name' => null,
                ];
            }
            
            $result[] = [
                'Oid' => $row->Oid,
                'Name' => $row->UserName,
                'BusinessPartner' => $businessPartner,
                'Currency' => $currency->Oid,
                "Rate" => $currency->getRate()->MidRate ?: 1,
                'Account' => $account
            ];
        }
        return $result;
    }

    public function posHome(Request $request)
    {
        $user = Auth::user();

        // $data = User::where('Oid', $user->Oid)->get();
        $data = User::with([
            'CompanyObj' => function ($query) {$query->addSelect('Oid', 'Code', 'Name');},
            ])->where('Oid', $user->Oid)->get();
        $session = POSSession::where('User', $user->Oid)->whereNull('Ended')->first();
        if (!$session) { return response()->json('Invalid Session', Response::HTTP_NOT_FOUND); }
    
        foreach ($data as $row){
            $row->POSSessionDate = $session->Date;
        }
        // return (new UserResource($data))->type('detail');

        return response()->json(
            $data,
            Response::HTTP_OK
        );       
    }
}
