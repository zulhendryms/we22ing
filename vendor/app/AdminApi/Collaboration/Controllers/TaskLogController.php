<?php

namespace App\AdminApi\Collaboration\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Collaboration\Entities\Task;
use App\Core\Collaboration\Entities\TaskLog;
use App\Core\Collaboration\Entities\TaskProject;
use App\Core\Master\Entities\Project;
use App\Core\Security\Entities\User;
use App\Core\Master\Entities\Company;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class TaskLogController extends Controller
{
    protected $roleService;
    private $crudController;
    private $module;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->module = 'coltasklog';
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
    }

    public function fields()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w' => 0, 'r' => 0, 't' => 'text', 'n' => 'Oid', 'fs' => 'c.Oid'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'CreatedAt', 'fs' => 'c.CreatedAt'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'CreatedBy', 'fs' => 'u.Name'];
        // $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'UpdatedAt', 'fs' => 'c.UpdatedAt'];
        // $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'UpdatedBy', 'fs' => 'us.Name'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'User1',  'fs' => 'u1.Name'];
        // $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'User2',  'fs' => 'u2.Name'];
        // $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'User3',  'fs' => 'u3.Name'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Title',  'fs' => 'c1.Title'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Project',  'fs' => 'p.Name'];
        $fields[] = ['w' => 300, 'r' => 0, 't' => 'text', 'n' => 'Description',  'fs' => 'c.Description'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Status',  'fs' => 'c1.Status'];
        return $fields;
    }

    public function config(Request $request)
    {
        try {
            $fields = $this->crudController->jsonConfig($this->fields(), false, true);
            $fields[0]['cellRenderer'] = 'actionCell';
            return $fields;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function list(Request $request)
    {
        $where = "";
        if ($request->has('Project')) {
            switch ($request->input('Project')) {
                case 'All':
                    $where = $where."";
                    break;
                case 'Enni':
                    $user = User::whereIn('Name', ['Enni'])->pluck('Oid')->first();
                    $where = $where."AND u.Oid IN ('$user')";
                    break;
                case 'Vivi':
                    $user = User::whereIn('Name', ['Vivi'])->pluck('Oid')->first();
                    $where = $where."AND u.Oid IN ('$user')";
                    break;
                case 'Admin':
                    $project = Project::whereIn('Code', ['Hokindo', 'Admin'])->get();
                    $str = null;
                    foreach($project as $p) $str = $str.($str ? "," : "")."'".$p->Oid."'";
                    $where = $where."AND p.Oid IN ($str)";
                    break;
                case 'TravelAdmin':
                    $project = Project::whereIn('Code', ['TravelAdmin'])->pluck('Oid')->first();
                    $where = $where."AND p.Oid IN ('$project')";
                    break;
                case 'TravelVue':
                    $project = Project::whereIn('Code', ['TravelVue'])->pluck('Oid')->first();
                    $where = $where."AND p.Oid IN ('$project')";
                    break;
                case 'Other':
                    $project = Project::whereIn('Code', ['Trucking', 'POS'])->get();
                    $str = null;
                    foreach($project as $p) $str = $str.($str ? "," : "")."'".$p->Oid."'";
                    $where = $where."AND p.Oid IN ($str)";
                    break;
            }
        }
        if ($request->has('User')) {
            switch ($request->input('User')) {
                case 'All':
                    $where = $where."";
                    break;
                case '1':
                    $user = User::whereIn('Name', ['William', 'Zul', 'Eka'])->get();
                    $str = null;
                    foreach($user as $u) $str = $str.($str ? "," : "")."'".$u->Oid."'";
                    $where = $where."AND u.Oid IN ($str)";
                    break;
                case '2':
                    $user = User::whereIn('Name', ['Dani', 'Vijay'])->get();
                    $str = null;
                    foreach($user as $u) $str = $str.($str ? "," : "")."'".$u->Oid."'";
                    $where = $where."AND u.Oid IN ($str)";
                    break;
                case '3':
                    $user = User::whereIn('Name', ['William', 'Zulhendry', 'Eka', 'Dani', 'Vijay'])->get();
                    $str = null;
                    foreach($user as $u) $str = $str.($str ? "," : "")."'".$u->Oid."'";
                    $where = $where."AND u.Oid IN ($str)";
                    break;
                default:
                    $user = User::where('Name', $request->input('User'))->first();
                    $where = $where."AND u.Oid IN ('$user')";
                    break;
            }
        }
        if ($request->has('Status')) {
            switch ($request->input('Status')) {
                case 'All':
                    $where = $where."";
                    break;
                case 'Open':
                    $task = Task::whereIn('Status', ['Open', 'Entry', 'Started', 'Urgent'])->distinct()->pluck('Status');
                    $where = $where."AND c1.Status IN ('Open', 'Entry', 'Started', 'Urgent')";
                    break;
                case 'Urgent':
                    $task = Task::whereIn('Status', ['Urgent'])->distinct()->get();
                    $where = $where."AND c1.Status IN ('Urgent')";
                    break;
                case 'Other':
                    $task = Task::whereIn('Status', ['Pending', 'Request'])->distinct()->pluck('Status');
                    $where = $where."AND c1.Status IN ('Pending','Request')";
                    break;
                case 'Completed':
                    $task = Task::whereIn('Status', ['Completed'])->distinct()->get();
                    $where = $where."AND c1.Status IN ('Completed')";
                break;
            }
        }
        if ($request->has('DateStart')) {
            $datefrom = Carbon::parse($request->input('DateStart'));
            $where = $where." AND DATE_FORMAT(c.CreatedAt, '%Y-%m-%d') >= '".$datefrom."'";
        }
        if ($request->has('DateUntil')) {
            $dateto = Carbon::parse($request->input('DateUntil'));
            $where = $where." AND DATE_FORMAT(c.CreatedAt, '%Y-%m-%d') <= '".$dateto."'";
        }
        
        $query = "SELECT c.Oid, 
            DATE_FORMAT(c.CreatedAt, '%e/%m/%y %h:%i:%s') AS CreatedAt , u.Name AS CreatedBy,
            DATE_FORMAT(c.UpdatedAt, '%e/%m/%y %h:%i:%s') AS UpdatedAt, us.Name AS UpdatedBy,
            u1.Name AS User1, u2.Name AS User2, u3.Name AS User3, 
            c1.Title, c.Description, p.Name AS Project, c1.Status 
            FROM coltasklog c
            LEFT OUTER JOIN coltask c1 ON c.Task = c1.Oid
            LEFT OUTER JOIN mstproject p ON c1.Project = p.Oid
            LEFT OUTER JOIN user u ON c.CreatedBy = u.Oid
            LEFT OUTER JOIN user us ON c.UpdatedBy = us.Oid
            LEFT OUTER JOIN user u1 ON c.User1 = u1.Oid
            LEFT OUTER JOIN user u2 ON c.User2 = u2.Oid
            LEFT OUTER JOIN user u3 ON c.User3 = u3.Oid
            WHERE c.GCRecord IS NULL ".$where."
            ORDER BY c.CreatedAt";
        $data = DB::select($query);
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function presearch(Request $request)
    {
        // return $this->crudController->presearch('coltask');
        return [
            [
                'fieldToSave' => "Project",
                'type' => "combobox",
                'hiddenField' => 'ProjectName',
                'column' => "1/6",
                'source' => [],
                'store' => "",
                'default' => "All",
                'source' => [
                    ['Oid' => 'All', 'Name' => 'All'],
                    ['Oid' => 'Enni', 'Name' => 'EN: All'],
                    ['Oid' => 'TravelAdmin', 'Name' => 'EN: Admin'],
                    ['Oid' => 'TravelVue', 'Name' => 'EN: Travel Vue'],
                    ['Oid' => 'Vivi', 'Name' => 'VV: All'],
                    ['Oid' => 'Admin', 'Name' => 'VV: Admin'],
                    ['Oid' => 'Other', 'Name' => 'VV: Others'],
                ]
            ],
            [
                'fieldToSave' => "User",
                'type' => "combobox",
                'hiddenField' => 'UserName',
                'column' => "1/6",
                'source' => [],
                'store' => "",
                'default' => "All",
                'source' => [
                    ['Oid' => 'All', 'Name' => 'All'],
                    ['Oid' => 'UN', 'Name' => 'Unassigned'],
                    ['Oid' => 'Dani', 'Name' => 'DN'],
                    ['Oid' => 'Eka', 'Name' => 'EK'],
                    ['Oid' => 'Enni', 'Name' => 'EN'],
                    ['Oid' => 'Vijay', 'Name' => 'VJ'],
                    ['Oid' => 'Vivi', 'Name' => 'VV'],
                    ['Oid' => 'William', 'Name' => 'WS'],
                    ['Oid' => 'Zulhendry', 'Name' => 'ZUL'],
                ]
            ],
            [
                'fieldToSave' => "Status",
                'type' => "combobox",
                'column' => "1/6",
                'source' => [],
                'store' => "",
                'default' => "Open",
                'source' => [
                    ['Oid' => 'Open', 'Name' => 'Open & Urgent'],
                    ['Oid' => 'Urgent', 'Name' => 'Urgent'],
                    ['Oid' => 'Other', 'Name' => 'Other'],
                    ['Oid' => 'Completed', 'Name' => 'Completed'],
                    ['Oid' => 'All', 'Name' => 'All'],
                ]
            ],
            [
                'fieldToSave' => 'DateStart',
                'type' => 'inputdate',
                'column' => '1/6',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->startOfMonth()->format('Y-m-d')
            ],
            [
                'fieldToSave' => 'DateUntil',
                'type' => 'inputdate',
                'column' => '1/6',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->endOfMonth()->format('Y-m-d')
            ],
            [
                'type' => 'action',
                'column' => '1/6'
            ]
        ];
    }
}
