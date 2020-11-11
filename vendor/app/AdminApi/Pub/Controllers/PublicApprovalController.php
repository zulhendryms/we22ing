<?php

namespace App\AdminApi\Pub\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\AdminApi\Chart\Controllers\DashboardChartController;
use App\Core\Pub\Entities\PublicApproval;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Trading\Entities\PurchaseOrder;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\CashBankSubmission;
use App\Core\Trading\Entities\SalesOrder;
use App\Core\Trucking\Entities\TruckingTransactionFuel;
use App\Core\Internal\Events\EventSendNotificationSocketOneSignal;
use App\Core\Internal\Entities\Status;
use App\Core\Master\Entities\Department;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Services\HttpService;
use App\Core\Accounting\Services\CashBankService;
use App\Core\Accounting\Services\JournalService;
use App\Core\Internal\Services\AutoNumberService;
use App\Core\Security\Entities\Notification;
use App\Core\Security\Entities\User;
use App\AdminApi\Development\Controllers\ServerDashboardController;
use App\Core\Base\Exceptions\UserFriendlyException;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

use Carbon\Carbon;

//CARA TAMBAH MODULE BARU:
//- Create field di publicpost
//- Tambahkan coding di publicapproval ikutin contoh CTRL+F
//- Tambahkan coding di action ikutin contoh
//- Tambahkan coding di entities ikutin contoh

class PublicApprovalController extends Controller
{
    private $publicPostController;
    protected $cashBankService;
    private $autoNumberService;
    private $serverDashboardController;
    private $crudController;

    public function __construct()
    {
        $this->publicPostController = new PublicPostController(new RoleModuleService(new HttpService), new HttpService);
        $this->cashBankService = new CashBankService(new JournalService);
        $this->autoNumberService = new AutoNumberService();
        $this->serverDashboardController = new ServerDashboardController();
        $this->crudController = new CRUDDevelopmentController();
    }

    public function fields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w'=> 140, 'r'=>0, 't'=>'text', 'n'=>'Oid', 'fs'=> 'data.Oid'];
        $fields[] = ['w'=> 180, 'r'=>0, 't'=>'text', 'n'=>'Company', 'fs'=> 'Company.Code'];
        $fields[] = ['w'=> 140, 'r'=>0, 't'=>'text', 'n'=>'Type'];
        $fields[] = ['w'=> 180, 'r'=>0, 't'=>'text', 'n'=>'Code', 'fs'=> 'PublicPost.Code'];
        $fields[] = ['w'=> 180, 'r'=>0, 't'=>'text', 'n'=>'Date', 'fs'=> 'PublicPost.Date'];
        $fields[] = ['w'=> 180, 'r'=>0, 't'=>'text', 'n'=>'Amount', 'fs'=> 'PublicPost.TotalAmount'];
        $fields[] = ['w'=> 180, 'r'=>0, 't'=>'text', 'n'=>'Status', 'fs'=> 'Status.Code'];
        $fields[] = ['w'=> 0, 'h'=>1, 't'=>'text', 'n'=>'ObjectOid', 'fs'=> 'data.ObjectOid'];
        return $fields;
    }

    public function presearch(Request $request) {
        return [
            [
                'fieldToSave' => "Type",
                'hideLabel' => true,
                'type' => "combobox",
                'hiddenField'=> 'TypeName',
                'column' => "1/3",
                'source' => [],
                'store' => "",
                'source' => [
                    ['Oid' => 'Requested', 'Name' => 'Requested'],
                    ['Oid' => 'Approval', 'Name' => 'Approval'],
                    ['Oid' => 'Upcoming', 'Name' => 'Upcoming'],
                    ['Oid' => 'Approved', 'Name' => 'Approved'],
                    ['Oid' => 'Rejected', 'Name' => 'Rejected'],
                ],
                'defaultValue' => "Approval"
            ],
            [
                'type' => 'action',
                'column' => '1/3'
            ]
        ];
    }
 
    public function config(Request $request) {
        $fields = $this->crudController->jsonConfig($this->fields(),false,true);
        $fields[0]['cellRenderer'] = 'actionCell';
        $fields[0]['topButton'] = [
            [
            'name' => 'New Purchase Request',
            'icon' => 'DocumentIcon',
            'type' => 'open_form',
            'url' => "purchaseorder/form?type=PurchaseRequest"
            ]
        ];
        return $fields;
    }

    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->has('Type') ? $request->input('Type') : 'Requested';
            $fields = $this->crudController->jsonConfig($this->fields(),false,true);
            $action = [];
            
            if ($type == 'Requested') {                
                // LEFT OUTER JOIN pubpost p ON data.ObjectOid = p.Oid OR data.PublicPost = p.Oid
                $query = "SELECT data.Oid FROM pubapproval data 
                    LEFT OUTER JOIN pubpost p ON data.ObjectOid = p.Oid
                    LEFT OUTER JOIN sysstatus s On s.Oid = p.Status
                    WHERE data.User = '{$user->Oid}' 
                    AND data.Action = 'Request'
                    AND data.Type IS NOT NULL
                    AND p.Oid IS NOT NULL
                    ORDER BY data.CreatedAt DESC LIMIT 30";
                $action[] = [
                        'name' => 'Change to REQUEST',
                        'icon' => 'UnlockIcon',
                        'type' => 'confirm',
                        'post' => 'publicapproval/request?Oid={Oid}',
                        'afterRequest' => 'init'
                ];   
            } elseif ($type == 'Approval') {
                $query = "SELECT data.Oid FROM pubapproval data 
                    LEFT OUTER JOIN pubpost p ON data.ObjectOid = p.Oid
                    LEFT OUTER JOIN sysstatus s On s.Oid = p.Status
                    LEFT OUTER JOIN pubapproval prev ON p.Oid = prev.ObjectOid AND prev.Sequence = data.Sequence - 1 AND prev.Action != 'Request'
                    WHERE s.Code = 'submit'
                    AND data.User = '{$user->Oid}' 
                    AND data.ActionDate IS NULL
                    AND data.Type IS NOT NULL
                    AND IFNULL(data.Action,'') != 'Request'
                    AND CASE WHEN data.Sequence = 1 THEN TRUE ELSE prev.ActionDate IS NOT NULL END
                    ORDER BY data.CreatedAt DESC LIMIT 30";
                $action[] = [
                        'name' => 'Approve',
                        'icon' => 'CheckCircleIcon',
                        'type' => 'global_form',
                        'form' => [
                            [ 'fieldToSave' => 'Note',
                            'type' => 'inputarea' ],
                        ],
                        'showModal' => false,
                        'post' => 'publicapproval/approve?Oid={Oid}',
                        'afterRequest' => 'init'
                    ]; 
                $action[] = [
                        'name' => 'Reject',
                        'icon' => 'XCircleIcon',
                        'type' => 'global_form',
                        'form' => [
                            [ 'fieldToSave' => 'Note',
                            'type' => 'inputarea' ],
                        ],
                        'showModal' => false,
                        'post' => 'publicapproval/reject?Oid={Oid}',
                        'afterRequest' => 'init'
                ];
            } elseif ($type == 'Upcoming') {
                $query = "SELECT data.Oid FROM pubapproval data 
                    LEFT OUTER JOIN pubapproval dataPrev 
                    ON data.Code = dataPrev.Code 
                    AND data.Company = dataPrev.Company 
                    AND data.Type = dataPrev.Type
                    AND dataPrev.Sequence = data.Sequence - 1
                    LEFT OUTER JOIN pubpost p ON data.ObjectOid = p.Oid
                    LEFT OUTER JOIN sysstatus s On s.Oid = p.Status
                    WHERE s.Code IN ('submit','entry','request')
                    AND data.User = '{$user->Oid}' 
                    AND data.ActionDate IS NULL
                    AND data.Sequence > 1 
                    AND dataPrev.ActionDate IS NULL
                    AND data.Type IS NOT NULL
                    AND IFNULL(data.Action,'') != 'Request'
                    ORDER BY dataPrev.ActionDate";
                $action = [];
            } elseif ($type == 'Approved') {
                $query = "SELECT data.Oid FROM pubapproval data 
                    LEFT OUTER JOIN pubpost p ON data.ObjectOid = p.Oid
                    LEFT OUTER JOIN sysstatus s On s.Oid = p.Status
                    WHERE data.User = '{$user->Oid}' 
                    AND data.Action = 'Approve'
                    AND data.ActionDate IS NOT NULL
                    AND data.Type IS NOT NULL
                    AND IFNULL(data.Action,'') != 'Request'
                    ORDER BY data.ActionDate DESC LIMIT 30";
                $action = [];
            
            } elseif ($type == 'Rejected') {
                $query = "SELECT data.Oid FROM pubapproval data 
                    LEFT OUTER JOIN pubpost p ON data.ObjectOid = p.Oid
                    LEFT OUTER JOIN sysstatus s On s.Oid = p.Status                        
                    WHERE data.User = '{$user->Oid}' 
                    AND data.Action = 'Reject'
                    AND data.ActionDate IS NOT NULL
                    AND data.Type IS NOT NULL
                    AND IFNULL(data.Action,'') != 'Request'
                    ORDER BY data.ActionDate DESC LIMIT 30";             
                $action = [];
            }
            $filter = DB::select($query);
            
            $filter = collect($filter)->pluck('Oid');

            // $fields = $this->crudController->jsonConfig($this->fields(),false,true);
            $data = DB::table('pubapproval as data') //jointable
                ->leftJoin('pubpost AS PublicPost', 'PublicPost.Oid', '=', 'data.ObjectOid')
                ->leftJoin('mstdepartment AS Department', 'Department.Oid', '=', 'PublicPost.Department')
                ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
                ->leftJoin('user AS User', 'User.Oid', '=', 'PublicPost.User')
                ->leftJoin('sysstatus AS Status', 'Status.Oid', '=', 'PublicPost.Status')
                ;
            if ($filter) $data->whereIn('data.Oid',$filter);
            $data = $this->crudController->jsonList($data, $this->fields(), $request, 'pubapproval', 'data.UpdatedAt');
            foreach ($data as $row) {
                // dd($row);
                if ($row->Type == 'PurchaseOrder') {
                    $tmp = PurchaseOrder::where('Oid',$row->ObjectOid)->first();
                    $row->Code = isset($tmp->RequestCode) ? $tmp->RequestCode : $row->Code;
                } elseif ($row->Type == 'CashBank') {
                    $tmp = CashBank::where('Oid',$row->ObjectOid)->first();
                    $row->Code = isset($tmp->RequestDate) ? $tmp->RequestDate : $row->Code;
                } elseif ($row->Type == 'CashBankSubmission') {
                    $tmp = CashBankSubmission::where('Oid',$row->ObjectOid)->first();
                }
                $new = [];
                $new = $action;
                if (!in_array($row->Type,['CashBankSubmission'])) $new[] = [
                    'name' => 'Open',
                    'icon' => 'ArrowUpRightIcon',
                    'type' => 'open_view',
                    'portalget' => $this->functionGetUrl($row,"view"),
                    'get' => $this->functionGetUrl($row,"get"),
                ];
                $new[] = [
                    'name' => 'Open in detail',
                    'icon' => 'ArrowUpRightIcon',
                    'type' => 'open_form',
                    'url' => $this->functionGetUrl($row),
                ];
                $row->Action = $new;
            }
            return $this->crudController->jsonListReturn($data, $this->fields());

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
    private function functionGetUrl($row, $type = null) {
        if ($row->Type == 'PurchaseOrder') {
            $tmp = PurchaseOrder::where('Oid',$row->ObjectOid)->first();
            if ($type == null) return "purchaseorder/form?item={ObjectOid}&type=".$tmp->Type."&returnUrl=publicapproval%3FType%3D{Url.Type}";
            elseif ($type == 'get') return "purchaseorder/{ObjectOid}";
            elseif ($type == 'view') return "development/table/vueview?code=PurchaseOrder";
        } elseif ($row->Type == 'CashBank') {
            $tmp = CashBank::where('Oid',$row->ObjectOid)->first();
            if ($type == null) return "cashbank/form?item={ObjectOid}&type=".$tmp->Type."&returnUrl=publicapproval%3FType%3D{Url.Type}";
            elseif ($type == 'get') return "cashbank/{ObjectOid}";
            elseif ($type == 'view') return "development/table/vueview?code=CashBank";
        } elseif ($row->Type == 'CashBankSubmission') {
            $tmp = CashBankSubmission::where('Oid',$row->ObjectOid)->first();
            if ($type == null) return "cashbanksubmission/form?item={ObjectOid}&returnUrl=publicapproval%3FType%3D{Url.Type}";
            elseif ($type == 'get') return "cashbanksubmission/{ObjectOid}";
            elseif ($type == 'view') return "development/table/vueview?code=CashBankSubmission";
        } elseif ($row->Type == 'TruckingTransactionFuel') {
            $tmp = TruckingTransactionFuel::where('Oid',$row->ObjectOid)->first();
            if ($type == null) return "truckingtransactionfuel/form?item={ObjectOid}&type=".$tmp->Type."&returnUrl=publicapproval%3FType%3D{Url.Type}";
            elseif ($type == 'get') return "truckingtransactionfuel/{ObjectOid}";
            elseif ($type == 'view') return "development/table/vueview?code=TruckingTransactionFuel";
        }
    }
    private function findDashboard($dashboardTemplates, $code) {
        foreach($dashboardTemplates as $row) if ($row->Code == $code) return $row;
    }
    
    public function dashboard(Request $request) {
        $user = Auth::user();        
        $chart = [];
        $dashboard = new DashboardChartController();
        $dashboardTemplates = $this->serverDashboardController->functionGetTemplateData();

        $param = $this->findDashboard($dashboardTemplates, '001-10');
        $param->Sequence = 1;
        $chart[] = $dashboard->chartListBulletin($param);
        
        $param = $this->findDashboard($dashboardTemplates, '001-11');
        $param->Sequence = 2;
        $chart[] = $dashboard->chartListBulletin($param);
        
        $param = $this->findDashboard($dashboardTemplates, '001-12');
        $param->Sequence = 3;
        $chart[] = $dashboard->chartListBulletin($param);
        
        $param = $this->findDashboard($dashboardTemplates, '001-13');
        $param->Sequence = 4;
        $chart[] = $dashboard->chartListBulletin($param);

        return $chart;
    }

    public function formAction($data, $module, $for) {
        $user = Auth::user();
        
        if ($for == 'submit') {
            $actionSubmit = [
                'name' => 'Submit',
                'icon' => 'ArrowUpCircleIcon',
                'type' => 'confirm',
                'post' => 'publicapproval/submit?'.$module.'={Oid}',
                'afterRequest' => 'init'
            ];
            if ($module != 'PurchaseOrder') return $actionSubmit;
            if (($data->DepartmentObj ? $data->DepartmentObj->Purchaser : null) == $user->Oid) return $actionSubmit;
            return [];
        }
        
        if ($for == 'request') return [
            'name' => 'Request',
            'icon' => 'ArrowUpCircleIcon',
            'type' => 'confirm',
            'post' => 'publicapproval/request?'.$module.'={Oid}',
            'afterRequest' => 'init'
        ];
        
        if ($for == 'entry') return [
            'name' => 'Change to Entry',
            'icon' => 'ArrowUpCircleIcon',
            'type' => 'confirm',
            'post' => 'publicapproval/entry?'.$module.'={Oid}',
            'afterRequest' => 'init'
        ];

        $actionApprove = [
            'name' => 'Approve',
            'icon' => 'CheckCircleIcon',
            'type' => 'global_form',
            'form' => [
              [ 'fieldToSave' => 'Note',
                'type' => 'inputarea' ],
            ],
            'showModal' => false,
            'post' => 'publicapproval/approve?'.$module.'={Oid}',
            'afterRequest' => 'init'
        ];
        $actionReject = [
            'name' => 'Reject',
            'icon' => 'actionReject',
            'type' => 'global_form',
            'form' => [
              [ 'fieldToSave' => 'Note',
                'type' => 'inputarea' ],
            ],
            'showModal' => false,
            'post' => 'publicapproval/reject?'.$module.'={Oid}',
            'afterRequest' => 'init'
        ];
        $approval = PublicApproval::where('ObjectOid', $data->Oid)->where('User',$user->Oid)->whereNull('ActionDate')->first();
        if ($approval) $approvalPrevious = PublicApproval::where('ObjectOid', $data->Oid)->where('Sequence',$approval->Sequence - 1)->whereNotNull('ActionDate')->first();
        $return = [];
        if ($approval) {
            if ($approval->Sequence == 1 || $approvalPrevious) {
                $return[] = $actionApprove;
                $return[] = $actionReject;
                return $return;
            } 
        }
        return [];
    }

    public function formApprovalReset($data) {
        foreach($data->Approvals as $row) {
            $row->Action = null;
            $row->ActionDate = null;
            $row->Note = null;
            $row->save();
        }        
    }

    private function newPublicApproval($data, $department, $sequence, $type) {
        try {
            if (!$department->{'Approval'.$sequence}) return;
            $tmp = new PublicApproval();
            $tmp->Company = $data->Company;
            $tmp->PublicPost = $data->Oid;
            $tmp->ObjectOid = $data->Oid;
            $tmp->Type = $type;
            $tmp->ObjectType = $type;
            $tmp->Sequence = $sequence;
            $tmp->User = $department->{'Approval'.$sequence};
            if ($sequence < 3) $tmp->NextUser = $department->{'Approval'.($sequence + 1)};
            $tmp->save();
        } catch (\Exception $e) { err_return($e); }
    }

    public function formCreate($data, $type) {
        try {
            $user = Auth::user();
            $department = Department::findOrFail($data->Department);
            DB::delete("DELETE FROM pubapproval WHERE ObjectOid = '{$data->Oid}'");
            $this->newPublicApproval($data, $department, 1, $type);
            $this->newPublicApproval($data, $department, 2, $type);
            $this->newPublicApproval($data, $department, 3, $type);
            if ($type == 'PurchaseOrder') $data->Purchaser = $department->Purchaser;
            $data->save();
            if ($type == 'PurchaseOrder') {
                if ($data->Purchaser != $data->CreatedBy) {
                    $tmp = new PublicApproval();
                    $tmp->Company = $data->Company;
                    $tmp->PublicPost = $data->Oid;
                    $tmp->ObjectOid = $data->Oid;
                    $tmp->Type = $type;
                    $tmp->User = $data->CreatedBy;
                    $tmp->Action = 'Request';
                    $tmp->save();
                }
            }            
        } catch (\Exception $e) { err_return($e); }
    }
    
    private function findData($request) {
        $oid = $request->input('Oid');
        if ($request->has('Oid')) {          
            $tmp = PublicApproval::findOrFail($request->input('Oid'));
            $module = $tmp->Type;
            $oid = $tmp->ObjectOid;
        } elseif ($request->has('CashBank')) {
            $module = 'CashBank';
            $oid = $request->input('CashBank');
        } elseif ($request->has('PurchaseOrder')) {
            $module = 'PurchaseOrder';
            $oid = $request->input('PurchaseOrder');
        } elseif ($request->has('SalesOrder')) {
            $module = 'SalesOrder';
            $oid = $request->input('SalesOrder');
        } elseif ($request->has('TruckingTransactionFuel')) {
            $module = 'TruckingTransactionFuel';
            $oid = $request->input('TruckingTransactionFuel');
        } elseif ($request->has('CashBankSubmission')) {
            $module = 'CashBankSubmission';
            $oid = $request->input('CashBankSubmission');
        }
        switch ($module) {
            case 'CashBank':
                return [
                    'data' => CashBank::where('Oid',$oid)->first(),
                    'module' => $module,
                ];
            case 'PurchaseOrder':
                return [
                    'data' => PurchaseOrder::where('Oid',$oid)->first(),
                    'module' => $module,
                ];
            case 'SalesOrder':
                return [
                    'data' => SalesOrder::where('Oid',$oid)->first(),
                    'module' => $module,
                ];
            case 'TruckingTransactionFuel':
                return [
                    'data' => TruckingTransactionFuel::where('Oid',$oid)->first(),
                    'module' => $module,
                ];
            case 'CashBankSubmission':
                return [
                    'data' => CashBankSubmission::where('Oid',$oid)->first(),
                    'module' => $module,
                ];
        }
    }

    private function clearNotication($data, $allUser = true) {
        $user = Auth::user();
        
        // $notifications = Notification::whereNull('GCRecord')
        //     ->where('ObjectOid', $data->Oid)
        //     ->whereNull('DateRead')
        //     ->where('User',$user->Oid)
        //     ->where('Type','Approve');
        
        $criteria = '';
        if (!$allUser) {
            $criteria = " AND User='".$user->Oid."' ";
            // $notifications = $notifications->where('User', $user->Oid);
        }

        $query = "UPDATE notification SET DateRead = now() 
            WHERE DateRead IS NULL             
            AND PublicPost='{$data->Oid}'
            AND Type='Approve' ".$criteria;
        DB::update($query);

        // $notifications = $notifications->get();
        // foreach($notifications as $row) {
        //     $row->DateRead = now();
        //     $row->save();
        // }
    }
    
    public function statusEntry(Request $request) {
        try {
            $user = Auth::user();
            $tmp = $this->findData($request);
            $data = $tmp['data'];
            $module = $tmp['module'];

            $this->clearNotication($data, true);
                            
            //VALIDATION
            $data->Status = Status::where('Code','entry')->first()->Oid;
            $data->save();
            
            $query = "UPDATE pubapproval SET ActionDate = NULL WHERE ObjectOid='{$data->Oid}'";
            DB::update($query);

            $this->publicPostController->sync($data, $module);

            return $data; //ga tau npa ga bisa pake return response json, terpaksa return sprt ini aja lgsg
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
    
    public function statusRequest(Request $request) {
        try {
            $user = Auth::user();
            $tmp = $this->findData($request);
            $data = $tmp['data'];
            $module = $tmp['module'];

            $this->clearNotication($data, true);
                            
            //VALIDATION
            $data->Status = Status::where('Code','request')->first()->Oid;
            $data->save();
            
            $query = "UPDATE pubapproval SET ActionDate = NULL
                WHERE ObjectOid='{$data->Oid}'";
            DB::update($query);

            $this->publicPostController->sync($data, $module);
            
            //NOTIFICATION NEXT USER
            if ($data->DepartmentObj->Purchaser && $data->DepartmentObj->Purchaser != $user->Oid) {
                $this->sendNotification($data, $module, $data->DepartmentObj->Purchaser,
                    ' needs Procesing',
                    'Requested by '.$user->UserName
                );
            }                

            return $data; //ga tau npa ga bisa pake return response json, terpaksa return sprt ini aja lgsg
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function sendNotification($data, $module, $user, $title, $message, $type = 'Notification') {
        if (in_array($module, ['PurchaseOrder','CashBank'])) $code = $data->RequestCode; 
        else $code = $data->Code;

        $userLogin = Auth::user();
        if (gettype($user) == 'string' && $user == $userLogin->Oid) return true;
        if (gettype($user) == 'array') {
            $user = removeDuplicateArray($user, [$userLogin->Oid]);            
            if (count($user) == 0) return true;
            if (count($user) == 1) $user = $user[0];
        }
        if (in_array($module, ['CashBankSubmission'])) $action = [
                'name' => 'Open in detail',
                'icon' => 'ArrowUpRightIcon',
                'type' => 'open_form',
                'newTab' => true,
                'url' => strtolower($module)."/form?item=".$data->Oid,
            ];
        else $action = [
            'name' => 'Open',
            'icon' => 'ArrowUpRightIcon',
            'type' => 'open_view',
            'portalget' => "development/table/vueview?code=".$module,
            'get' => strtolower($module)."/".$data->Oid,
        ];
        $param = [
            'User' => $user,
            'Company' => $data->Company,
            'Type' => $module,
            'PublicPost' => $data->Oid,
            'ObjectType' => $module,
            'Icon' => 'CheckCircleIcon',
            'Color' => 'primary',
            'Code' => $code,
            'Title' => $code.$title, //PR-20132 needs Approval
            'Message' => $message, //Approved by Victor (message)
            'Action' => $action,
            'Type' => 'Notification',
        ];
        event(new EventSendNotificationSocketOneSignal($param));
    }

    public function statusSubmit(Request $request) {        
        try {
            $user = Auth::user();

            $tmp = $this->findData($request);
            $data = $tmp['data'];
            $module = $tmp['module'];
            if (!$data->Department) throw new \Exception("Department must be filled");

            if (in_array($module, ['PurchaseOrder'])) if (!$data->SupplierChosen || $data->SupplierChosen == 0) throw new \Exception("Business Partner must be chosen");
            if (in_array($module, ['PurchaseOrder','CashBank'])) if ((isset($data->TotalAmount) ? $data->TotalAmount : 0) < 1) throw new \Exception("Total cannot be zero");
            if (in_array($module, ['PurchaseOrder'])) if (!$data->BusinessPartner) throw new \Exception("Business Partner must be filled");

            $this->clearNotication($data, true);
            
            //VALIDATION
            $data->Status = Status::where('Code','submit')->first()->Oid;
            $data->save();
            $this->publicPostController->sync($data, $module);            
                            
            //NOTIFICATION NEXT USER
            $approval = PublicApproval::where("ObjectOid",$data->Oid)->where('Sequence',1)->first();
            $userApproval = [];
            if ($approval) {
                $userApproval = $approval->User;
            } else {
                if ($tmp['module'] == 'CashBank') $this->cashBankService->post($data->Oid);
                else {
                    $data->Status = Status::where('Code','posted')->first()->Oid;
                    $data->save();
                }
                $this->publicPostController->sync($data, $module);
            }

            //sendnotification
            $this->sendNotification($data, $module, $userApproval,
                ' needs Approval',
                'Submitted by '.$user->UserName
            );
                
            return $data; //ga tau npa ga bisa pake return response json, terpaksa return sprt ini aja lgsg
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusApprove(Request $request) {
        try {            
            $user = Auth::user();
            $tmp = $this->findData($request);
            $data = $tmp['data'];
            $module = $tmp['module'];
            
            //VALIDATION
            $approval = PublicApproval::where('ObjectOid', $data->Oid)->where('User',$user->Oid)->whereRaw("IFNULL(Action,'') != 'Request'")->whereNull('ActionDate')->first();
            if (!$approval) throw new UserFriendlyException("User is failed to approve");
            $approvalPrevious = PublicApproval::where('ObjectOid', $data->Oid)->where('Sequence',$approval->Sequence - 1)->whereRaw("IFNULL(Action,'') != 'Request'")->whereNotNull('ActionDate')->first();
            $approvalNext = PublicApproval::where('ObjectOid', $data->Oid)->where('Sequence',$approval->Sequence + 1)->whereRaw("IFNULL(Action,'') != 'Request'")->first();
            if ($approval->Sequence > 1 && !$approvalPrevious) throw new UserFriendlyException('You are failed to approve this request');
            
            $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
            $approval->Action = 'Approve';
            $approval->ActionDate = now()->addHours(company_timezone())->toDateTimeString();
            if (isset($request->Note)) $approval->Note = $request->Note;
            $approval->save();
            
            $this->clearNotication($data, false);
            
            //POSTING CASHBANK
            if (!$approvalNext) {                            
                //posting cashbank
                if ($tmp['module'] == 'CashBank') {
                    $this->cashBankService->post($data->Oid);
                } elseif ($tmp['module'] == 'PurchaseOrder') {
                    $data->Type = 'PurchaseOrder';
                    $data->Code = '<<Auto>>'; //supaya dia ulang generate;
                    $data->Code = $this->autoNumberService->generate($data, 'trdpurchaseorder');
                    $data->Status = Status::where('Code','posted')->first()->Oid;
                    $data->save();
                } else { //SELAIN CASHBANK & PURCHASEORDER, ex: transactionfuel, cahsbanksubmission, & yg baru
                    $data->Status = Status::where('Code','posted')->first()->Oid;
                    $data->save();
                }
                $this->publicPostController->sync($data, $module);
            }

            //NOTIFICATION NEXT USER
            $message = 'Approved by ' . $user->Name.(isset($request->Note) ? ' - '.$request->Note : null);
            $userApproval = [];
            if ($approvalNext) {
                $this->sendNotification($data, $module, $approvalNext->User,
                    ' needs Approval',
                    $message
                );
            }
            
            //NOTIFICATION FOR PURCHASER
            if (!$approvalNext) {
                $purchaser = isset($data->DepartmentObj->Purchaser) ? $data->DepartmentObj->Purchaser : null;
                if (in_array($module, ['PurchaseOrder','CashBank','TruckingTransactionFuel','CashBankSubmission'])) {
                    if (isset($purchaser)) {
                        $this->sendNotification($data, $module, $purchaser,
                            ' needs Procesing',
                            $message
                        );
                    }
                    //notification for others
                    $userNotif = [];
                    $userNotif[] = $data->createdBy;
                    if (isset($data->DepartmentObj->UserNotification1)) $userNotif[] = $data->DepartmentObj->UserNotification1;
                    if (isset($data->DepartmentObj->UserNotification2)) $userNotif[] = $data->DepartmentObj->UserNotification2;
                    if (isset($data->DepartmentObj->UserNotification3)) $userNotif[] = $data->DepartmentObj->UserNotification3;
                    $userNotif = removeDuplicateArray($userNotif, [$user, $purchaser]);
                    $this->sendNotification($data, $module, $userNotif,
                        ' is notified',
                        $message, 'Log'
                    );
                }
            }

            return $data; //ga tau npa ga bisa pake return response json, terpaksa return sprt ini aja lgsg
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
    
    public function statusReject(Request $request) {
        try {
            $user = Auth::user();

            $tmp = $this->findData($request);
            $data = $tmp['data'];
            $module = $tmp['module'];
            
            //VALIDATION
            $approval = PublicApproval::where('ObjectOid', $data->Oid)->where('User',$user->Oid)->whereNull('ActionDate')->first();
            $approvalPrevious = PublicApproval::where('ObjectOid', $data->Oid)->where('Sequence',$approval->Sequence - 1)->whereNotNull('ActionDate')->first();
            $approvalNext = PublicApproval::where('ObjectOid', $data->Oid)->where('Sequence',$approval->Sequence + 1)->first();
            if (!$approval) throw new UserFriendlyException('You are failed to approve this request');
            if ($approval->Sequence > 1 && !$approvalPrevious) throw new UserFriendlyException('You are failed to approve this request');                
            
            $this->clearNotication($data, true);

            $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
            $approval->Action = 'Reject';
            $approval->ActionDate = now()->addHours(company_timezone())->toDateTimeString();
            if (isset($request->Note)) $approval->Note = $request->Note;
            $approval->save();
            
            $data->Status = Status::where('Code','reject')->first()->Oid;
            $data->save();
            
            $this->publicPostController->sync($data, $module);

            $message = 'Rejected by '.$user->Name.(isset($request->Note) ? ' - '.$request->Note : null);
            if ($module == 'PurchaseOrder') {
                if (isset($data->DepartmentObj->Purchaser)) {
                    $this->sendNotification($data, $module, $data->DepartmentObj->Purchaser,
                        ' is already rejected',
                        ' '.$message, 
                        'Reject'
                    );
                }
            }
                
            return $data; //ga tau npa ga bisa pake return response json, terpaksa return sprt ini aja lgsg
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }


}
