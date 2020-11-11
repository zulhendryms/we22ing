<?php

namespace App\AdminApi\Security\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Entities\User;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Services\HttpService;
use App\Core\POS\Entities\POSSession;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Trucking\Entities\TruckingWorkOrder;
use App\Core\Internal\Entities\Role;
use App\Core\Internal\Services\FileCloudService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\AdminApi\Development\Controllers\ServerCRUDController;

class UserController extends Controller
{
    private $httpService;
    protected $roleService;
    protected $fileCloudService;
    private $module;
    private $crudController;
    private $serverCRUD;
    public function __construct(
        FileCloudService $fileCloudService,
        RoleModuleService $roleService,
        HttpService $httpService
        )
    {
        $this->roleService = $roleService;
        $this->httpService = $httpService;
        $this->fileCloudService = $fileCloudService;
        $this->httpService->baseUrl('http://api1.ezbooking.co:888')->json();
        $this->module = 'user';
        $this->crudController = new CRUDDevelopmentController();
        $this->serverCRUD = new ServerCRUDController();
    }

    public function fields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = ['w'=> 250, 'n'=>'UserName'];
        $fields[] = serverSideConfigField('Name');
        $fields[] = serverSideConfigField('IsActive');
        return $fields;
    }

    public function config(Request $request)
    {
        try {
            $fields = $this->crudController->jsonConfig($this->fields(), false, true);
            $fields[0]['topButton'] =[
                [
                    'name' => 'Create User',
                    'icon' => 'UserIcon',
                    'type' => 'global_form',
                    'showModal' => false,
                    'portalpost' => 'auth/register',
                    'afterRequest'=>'init',
                    'form' => [
                        [
                            'fieldToSave'=> 'Company',
                            'type'=> 'combobox',
                            "validationParams" => "required",
                            'source'=> 'company',
                            'onClick'=> [
                                'action'=> 'request',
                                'store'=> 'combosource/company',
                                'params'=> null
                            ]
                        ],
                        [ 
                            'fieldToSave' => 'BusinessPartner',
                            "validationParams" => "required",
                            'type' => "autocomplete",
                            "source" => [],
                            "store"=> "autocomplete/businesspartner",
                            'default' => [
                                "localCompany",
                                "BusinessPartner"
                            ],
                        ],
                        [ 
                            'fieldToSave' => 'UserName',
                            'type' => 'inputtext',
                            "validationParams" => "required",
                        ],
                        [ 
                            'fieldToSave' => 'Password',
                            'type' => 'inputtext',
                            "validationParams" => "required",
                        ],
                        [ 
                            "fieldToSave" => "Role",
                            "type" => "combobox",
                            "source" => Role::select('Oid','Name')->get(),
                            "validationParams" => "required",
                        ],
                    ]
                ]
            ];
            return $fields;
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function list(Request $request)
    {
        return User::addSelect('Oid','Name','UserName','IsActive')
            ->whereNull('GCRecord')
            ->whereNotNull('BusinessPartner')
            ->whereNull('Employee')
            ->orderBy('Name')
            ->get();
        // try {
        //     $fields = $this->crudController->jsonConfig($this->fields());
        //     // $data = User::addSelect('Oid','Name','UserName','IsActive')->get();
        //     $data = User::whereNull('GCRecord');
        //     // return $data;
        //     // $data = $this->crudController->list($this->module, $data, $request);
        //     $data = $this->crudController->jsonList($data, $this->fields(), $request, 'user', 'Name');
        //     // $role = $this->roleService->list('User'); //rolepermission
            
        //     foreach ($data as $row) {
        //         $row->Action = $this->action($row);
        //         // $row->Action = $this->roleService->generateActionMaster($role);
        //     }   
            
        //     return response()->json($data, Response::HTTP_OK);
        // } catch (\Exception $e) {
        //     errjson($e);
        // }        
    }


    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = User::whereNull('GCRecord');

            if ($type == 'combo') $data->select('Oid', 'UserName','Name');
            if ($request->has('truckingdriver')) $data->whereHas('RoleObj', function ($query) {
                // $query->where('IsTruckingDriver', true)->whereNotNull('IsTruckingDriver')->where('IsTruckingDriver','!=',false);
                $query->where('IsTruckingDriver', true);
            });
            if ($request->has('company')) $data->where('Company', $user->Company);

            $data = $data->orderBy('Name')->get();
            if ($request->has('truckingdriver')) {
                foreach($data as $row) {
                    $order = TruckingWorkOrder::where('Oid',$row->TruckingWorkOrder)->first();
                    $lastPosition = $order ? ($order->ToAddressObj ? " - ".$order->ToAddressObj->Name : null) : null;
                    $row->Name = $row->Name.' - '.$row->UserName.$lastPosition;                   
                }
            }
            // $role = $this->roleService->list('User'); //rolepermission
            // foreach ($data as $row) $row->Action = $this->roleService->generateActionMaster($role);
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
        $data = User::findOrFail($Oid);

        $data->BusinessPartnerName = $data->BusinessPartnerObj ? $data->BusinessPartnerObj->Name : null;
        $data->CurrencyName = $data->CurrencyObj ? $data->CurrencyObj->Name : null;
        $data->TimezoneName = $data->TimezoneObj ? $data->TimezoneObj->Name : null;
        $data->CountryName = $data->CountryObj ? $data->CountryObj->Name : null;
        $data->InvitorUserName = $data->InvitorUserObj ? $data->InvitorUserObj->Name : null;
        $data->CityName = $data->CityObj ? $data->CityObj->Name : null;
        $data->RoleName = $data->RoleObj ? $data->RoleObj->Name : null;
        $data->CompanyName = $data->CompanyObj ? $data->CompanyObj->Code : null;
        $data->DefaultCompanyName = $data->DefaultCompanyObj ? $data->DefaultCompanyObj->Name : null;
        $data->Action = $this->action($data);

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
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = User::where('Oid', $Oid)->first();
                $data = $this->crudController->saving($this->module, $request, $Oid, true);

                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('User'); //rolepermission
            $data = $this->showSub($data->Oid);
            // $data->Role = $this->roleService->generateActionMaster($role);
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

    public function action($data)
    {
        $url = 'user';
        $actionReset = [
            'name' => 'Change Password',
            'icon' => 'UnlockIcon',
            'type' => 'global_form',
            'form' => [
              [ 'fieldToSave' => 'OldPassword',
                'type' => 'inputtext' ],
              [ 'fieldToSave' => 'NewPassword',
                'type' => 'inputtext' ],
            ],
            'showModal' => false,
            'portalpost' => 'auth/reset/{Oid}',
        ];
        $actionDelete = [ 
            'name' => 'Delete',
            'icon' => 'TrashIcon',
            'type' => 'confirm',
            'delete' => $url.'/{Oid}'
        ];
        $return[] = $actionReset;
        $return[] = $actionDelete;
        return $return;
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

    public function destroy(User $data)
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

    public function favouriteGet() {
        try {
            $user = Auth::user();
            if (!$user->MenuFavourite) return null;
            $criteria = null;
            $tmp = json_decode($user->MenuFavourite);
            foreach($tmp as $row) $criteria = $criteria.($criteria ? "," : "")."'".strtolower($row)."'";
            // $data = $this->serverCRUD->getDataModule(" AND (LCASE(Code) IN (".$criteria.") OR LCASE(Name) IN (".$criteria.") OR LCASE(Url) IN (".$criteria."))");
            $data = $this->serverCRUD->getDataModule(" AND (LCASE(Code) IN (".$criteria.") OR LCASE(Name) IN (".$criteria.") OR LCASE(Url) IN (".$criteria."))");
            $result = [];
            foreach($data as $row) {
                $result[] = [
                    'url'=> '/'.$row->Url,
                    'name'=> $row->Name,
                    'icon'=> $row->Icon,
                    'color'=> 'primary',
                    'slug'=> $row->Code,
                    'i18n'=> $row->Code
                ];
            }
            return $result;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
    public function favouritePost(Request $request) {
        try {
            $user = User::where('Oid',Auth::user()->Oid)->first();
            $request = requestToObject($request);
            $data = [];
            $found=false;
            if ($user->MenuFavourite) $arr = json_decode($user->MenuFavourite);
            foreach ($arr as $a) $data[] = $a;
            foreach($data as $row) if (!$found) if ($row == $request->Modules) $found = true;
            if (!$found) {
                $data[] = isset($request->Modules) ? $request->Modules : null;
                $user->MenuFavourite = json_encode($data);
                $user->save();
            }
            return json_decode($user->MenuFavourite);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function favouriteRemove(Request $request) {
        try {
            $request = requestToObject($request);
            $user = User::where('Oid',Auth::user()->Oid)->first();
            $data = [];
            if ($user->MenuFavourite) $arr = json_decode($user->MenuFavourite);
            foreach ($arr as $row) {
                if ($row !== $request->Modules) $data[] = $row;
            }
            $user->MenuFavourite = json_encode($data);
            $user->save();
            return null;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
