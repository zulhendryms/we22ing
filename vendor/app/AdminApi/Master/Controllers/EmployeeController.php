<?php

namespace App\AdminApi\Master\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Employee;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\Core\Internal\Entities\Role;

class EmployeeController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->module = 'mstemployee';
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

    public function getDefault(Request $request) {
        $data = Employee::findOrFail($request->input('Oid'));
        $role = Role::select('Oid','Name')->first();
        return [
            'Oid'=>$data->Oid,
            'Employee'=>$data->Oid,
            'EmployeeName'=>$data->Name,
            'Company'=>$data->Company,
            'CompanyName'=>$data->CompanyObj->Name,
            'UserName'=>str_replace(" ","",$data->Name),
            'Password'=>"1234",
            "Role"=>$role->Oid,
            "RoleName"=>$role->Name,
        ];
    }

    public function list(Request $request)
    {
        try {
            $data = DB::table($this->module . ' as data');
            $data = $this->crudController->list($this->module, $data, $request,false);
            foreach($data->data as $row) {
                if (!$row->User) $row->Action[] = [
                    'name' => 'Create Login',
                    'icon' => 'UserIcon',
                    'type' => 'global_form',
                    'showModal' => false,
                    'get' => 'employee/default?Oid='.$row->Oid,
                    'portalpost' => 'auth/register',
                    'afterRequest'=>'init',
                    'form' => [
                        [
                            'fieldToSave'=> 'Company',
                            'type'=> 'combobox',
                            // 'disabled'=> true,
                            'validationParams'=> 'required',
                            'source'=> 'company',
                            'onClick'=> [
                                'action'=> 'request',
                                'store'=> 'combosource/company',
                                'params'=> null
                            ]
                        ],
                        [
                            'fieldToSave'=> 'Employee',
                            'type'=> 'combobox',
                            // 'disabled'=> true,
                            'validationParams'=> 'required',
                            'source'=> 'employee',
                            'onClick'=> [
                                'action'=> 'request',
                                'store'=> 'employee',
                                'params'=> null
                            ]
                        ],
                        [ 
                            'fieldToSave' => 'UserName',
                            'type' => 'inputtext',
                            'minLength' => 6,
                        ],
                        [ 
                            'fieldToSave' => 'Password',
                            'type' => 'inputtext' 
                        ],
                        [ 
                            "fieldToSave" => "Role",
                            "type" => "combobox",
                            "source" => Role::select('Oid','Name')->get(),
                        ],
                    ]                
                ];
                if ($row->User) {
                    $row->Action[] = [
                        'name' => 'Edit User Login',
                        'icon' => 'UserIcon',
                        'type' => 'global_form',
                        'showModal' => false,
                        'get' => 'user/'.$row->User,
                        'post' => 'user/'.$row->User,
                        'form' => [
                            [
                                'fieldToSave'=> 'CompanySource',
                                'type'=> 'combobox',
                                'source'=> 'combosource/company',
                                'onClick'=> [
                                    'action'=> 'request',
                                    'store'=> 'combosource/company',
                                    'params'=> null
                                ]
                            ],
                            [ 
                                'fieldToSave' => 'UserName',
                                'type' => 'inputtext',
                                'disabled' => true,
                            ],
                            [ 
                                'fieldToSave' => 'Name',
                                'type' => 'inputtext',
                            ],
                            [
                                "fieldToSave" => "Role",
                                "type" => "combobox",
                                "source" => Role::select('Oid', 'Name')->get(),
                            ],
                            [
                                'fieldToSave' => "CompanyAccess",
                                'type' => "inputtext",
                                'default' => null,
                            ],
                            [
                                'fieldToSave' => 'IsActive',
                                'type' => 'checkbox'
                            ],
                            [
                                'fieldToSave' => 'Image',
                                'type' => 'image'
                            ],
                        ]
                    ];
                    $row->Action[] = [
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
                        'portalpost' => 'auth/reset/'.$row->User,
                    ];
                }
            }
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

    public function show(Employee $data)
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

    public function destroy(Employee $data)
    {
        return $this->crudController->delete($this->module, $data);
    }

    public function savetoken(Request $request, $Oid = null)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF
        $dataArray = object_to_array($request);

        $messsages = array(
            'Code.required' => __('_.Code') . __('error.required'),
            'Code.max' => __('_.Code') . __('error.max'),
            'Name.required' => __('_.Name') . __('error.required'),
            'Name.max' => __('_.Name') . __('error.max'),
            'EmployeePosition.required' => __('_.EmployeePosition') . __('error.required'),
            'EmployeePosition.exists' => __('_.EmployeePosition') . __('error.exists'),
            'TravelGuideGroup.exists' => __('_.TravelGuideGroup') . __('error.exists'),
        );
        $rules = array(
            'Code' => 'required|max:255',
            'Name' => 'required|max:255',
            'EmployeePosition' => 'required|exists:mstemployeeposition,Oid',
            'Religion' => 'numeric',
            'Sex' => 'numeric',
            'TravelGuideGroup' => 'required|exists:trvguidegroup,Oid',
        );

        $validator = Validator::make($dataArray, $rules, $messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            if (!$Oid) $data = new Employee();
            else $data = Employee::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                // $data->Company = Auth::user()->Company;
                $data->Code = $request->Code == '<<Auto>>' ? now()->format('ymdHis') . '-' . str_random(3) : $request->Code;
                $data->Name = $request->Name;
                $data->EmployeePosition = $request->EmployeePosition;
                $data->IsActive = $request->IsActive;
                $data->save();
            });

            return $data;
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

    public function sendEmployee()
    {
        try {
            $bprole = BusinessPartnerRole::where('Code', 'TrvOutlet')->first();
            $bp = BusinessPartner::where('BusinessPartnerRole', $bprole->Oid)->whereNotNull('Token')->get();
            $employee = Employee::whereNull('GCRecord')->whereHas('EmployeePositionObj', function ($query) {
                $query->where('Code', company()->POSEmployeeFilter);
            })->where('IsActive', true)->get();

            foreach ($bp as $row) {
                $this->coreService->postapi("/admin/api/v1/employee/send", $row->Token, ['Data' => $employee]);
            }

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

    public function receiveEmployee(Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

        try {
            foreach ($request->Data as $row) {

                $data = BusinessPartner::where('Oid', $row->Oid)->first();
                if (!$data) $data = new BusinessPartner();

                $bprole = BusinessPartnerRole::where('Code', 'Customer')->first();
                $bpg = BusinessPartnerGroup::where('BusinessPartnerRole', $bprole->Oid)->first();
                $data->Company = company()->Oid;
                $data->Oid = $row->Oid;
                $data->Code = $row->Code;
                $data->Name = $row->Name;
                $data->BusinessPartnerRole = $bprole->Oid;
                $data->BusinessPartnerGroup = $bpg->Oid;
                $data->BusinessPartnerAccountGroup = $bpg->BusinessPartnerAccountGroup ?: null;
                if ($bpg->BusinessPartnerAccountGroup) {
                    $bpag = BusinessPartnerAccountGroup::findOrFail($bpg->BusinessPartnerAccountGroup);
                    $data->IsPurchase = $bpag->IsPurchase;
                    $data->IsSales = $bpag->IsSales;
                    $data->PurchaseCurrency = $bpag->PurchaseCurrency;
                    $data->SalesCurrency = $bpag->SalesCurrency;
                    $data->PurchaseTax = $bpag->PurchaseTax;
                    $data->PurchaseTerm = $bpag->PurchaseTerm;
                    $data->SalesTax = $bpag->SalesTax;
                    $data->SalesTerm = $bpag->SalesTerm;
                }
                $data->City = company()->City;
                $data->IsActive = 1;
                $data->save();
                logger($data);
            }
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
