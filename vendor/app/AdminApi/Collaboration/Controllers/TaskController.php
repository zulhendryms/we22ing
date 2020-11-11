<?php

namespace App\AdminApi\Collaboration\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Company;
use App\Core\Collaboration\Entities\Task;
use App\Core\Collaboration\Entities\TaskTemp;
use App\Core\Collaboration\Entities\TaskLog;
use App\Core\Collaboration\Entities\TaskProject;
use App\Core\Master\Entities\Project;
use App\Core\Security\Entities\User;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Services\HttpService;
use App\AdminApi\Pub\Controllers\PublicPostController;
use App\Core\Master\Entities\Image;
use App\Core\Pub\Entities\PublicComment;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Pub\Entities\PublicApproval;
use App\Core\Pub\Entities\PublicFile;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;


class TaskController extends Controller
{
    private $module;
    protected $roleService;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->module = 'coltask';
        $this->roleService = $roleService;
        $this->publicPostController = new PublicPostController(new RoleModuleService(new HttpService), new HttpService);
        $this->crudController = new CRUDDevelopmentController();
    }

    public function field(Request $request) {
        $fieldForm = [
            [
                'fieldToSave' => "Title",
                'type' => "inputtext",
                'default' => null,
            ],
            [
                'fieldToSave' => "Description",
                'overrideLabel' => "Description / Cronology / JSON API",
                'type' => "inputeditor",
                'default' => null,
            ],
            [
                'fieldToSave' => "Project",
                'hiddenField' => "ProjectName",
                'type' => "combobox",
                'column' => "1/3",
                'default' => null,
                'source' => "project",
                'onClick' => [
                    "action" => "request",
                    "params" => null,
                    "store" => "data/project",
                ],
            ],
            [
                'fieldToSave' => "TechnicalDatabase",
                'overrideLabel' => "Customer / DB / Login",
                'type' => "inputtext",
                'default' => null,
                'column' => "1/3",
            ],
            [
                'fieldToSave' => "Keyword",
                'overrideLabel' => "Requestor (User)",
                'type' => "inputtext",
                'default' => null,
                'column' => "1/3",
            ],
            [
                'fieldToSave' => "TechnicalAPIUrl",
                'overrideLabel' => "URL API",
                'type' => "inputtext",
                'default' => null,
            ],
            [
                'fieldToSave' => "URL",
                'overrideLabel' => "URL Website",
                'type' => "inputtext",
                'default' => null,
            ],
        ];        
        $fieldUser = [
            [
                'fieldToSave' => "User",
                'hiddenField' => "UserName",
                'type' => "autocomplete",
                'column' => "1/3",
                'default' => null,
                'source' => [],
                'store' => "autocomplete/user",
                'disabled' => false,
                'params' => [
                    'type' => 'combo',
                    'term' => ''
                ]
            ],
            [
                'fieldToSave' => "User2",
                'hiddenField' => "User2Name",
                'type' => "autocomplete",
                'column' => "1/3",
                'default' => null,
                'source' => [],
                'store' => "autocomplete/user",
                'disabled' => false,
                'params' => [
                    'type' => 'combo',
                    'term' => ''
                ]
            ],
            [
                'fieldToSave' => "User3",
                'hiddenField' => "User3Name",
                'type' => "autocomplete",
                'column' => "1/3",
                'default' => null,
                'source' => [],
                'store' => "autocomplete/user",
                'disabled' => false,
                'params' => [
                    'type' => 'combo',
                    'term' => ''
                ]
            ],
        ];
        if ($request->input('type') == 'create') return array_merge($fieldForm, $fieldUser);
        elseif ($request->input('type') == 'edit') return $fieldForm;
        elseif ($request->input('type') == 'user') return $fieldUser;
    }

    private function popup($isCreate = true)
    {
        $data = [
            'name' => 'Quick '.($isCreate ? 'Add' : 'Edit'),
            'icon' => 'PlusIcon',
            'type' => 'global_form',
            'showModal' => false,
            'post' => 'task',
            'afterRequest' => "apply",
            'config' => 'task/field?type='.($isCreate ? 'create' : 'edit')
        ];
        if ($isCreate) {
            $data['post'] = 'task';
        } else {
            $data['get'] = 'task/{Oid}';
            $data['post'] = 'task/{Oid}';
        }
        return $data;
    }

    public function config(Request $request)
    {
        try {
            $data = $this->crudController->config($this->module);
            $data[0]->topButton = [$this->popup(true)];
            $i = 0;
            foreach ($data as $row) {
                $data[$i]->cellStyle = [
                    // 'border' => '0.1px solid #f2f2f2',
                    'paddingLeft' => '5px !important',
                    'paddingRight' => '1px !important',
                    'fontSize' => '10px'
                ];
                $i = $i + 1;
            }
            return response()->json($data, Response::HTTP_OK);
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
            $data = DB::table('coltask as data');
            $mode = $request->has('Mode') ? $request->input('Mode') : 'view';
            if ($request->has('Project')) {
                switch ($request->input('Project')) {
                    case 'All':
                        break;
                    case 'Enni':
                        $user = User::whereIn('Name', ['Enni'])->pluck('Oid');
                        $data = $data->whereIn('data.UserFinal', $user);
                        break;
                    case 'Vivi':
                        $user = User::whereIn('Name', ['Vivi'])->pluck('Oid');
                        $data = $data->whereIn('data.UserFinal', $user);
                        break;
                    case 'Admin':
                        $project = Project::whereIn('Code', ['Hokindo', 'Admin'])->pluck('Oid');
                        $data = $data->whereIn('data.Project', $project);
                        break;
                    case 'TravelAdmin':
                        $project = Project::whereIn('Code', ['TravelAdmin'])->pluck('Oid');
                        $data = $data->whereIn('data.Project', $project);
                        break;
                    case 'TravelVue':
                        $project = Project::whereIn('Code', ['TravelVue'])->pluck('Oid');
                        $data = $data->whereIn('data.Project', $project);
                        break;
                    case 'Other':
                        $project = Project::whereIn('Code', ['Trucking', 'POS'])->pluck('Oid');
                        $data = $data->whereIn('data.Project', $project);
                        break;
                }
            }
            if ($request->has('User')) {
                switch ($request->input('User')) {
                    case 'All':
                        break;
                    case 'UN':
                        $data = $data->whereNull('data.User');
                        break;
                    case '1':
                        $user = User::whereIn('Name', ['William', 'Zul', 'Eka'])->pluck('Oid');
                        $data = $data->whereIn('data.User', $user);
                        break;
                    case '2':
                        $user = User::whereIn('Name', ['Dani', 'Vijay'])->pluck('Oid');
                        $data = $data->whereIn('data.User', $user);
                        break;
                    case '3':
                        $user = User::whereIn('Name', ['William', 'Zulhendry', 'Eka', 'Dani', 'Vijay'])->pluck('Oid');
                        $data = $data->whereIn('data.User', $user);
                        break;
                    default:
                        $user = User::where('Name', $request->input('User'))->first();
                        if ($user) $data = $data->where('data.User', $user->Oid);
                        break;
                }
            }
            if ($request->has('Status')) {
                switch ($request->input('Status')) {
                    case 'All':
                        break;
                    case 'Open':
                        $data = $data->whereIn('data.Status', ['Open', 'Entry', 'Started', 'Urgent', 'Today', null]);
                        break;
                    case 'Urgent':
                        $data = $data->whereIn('data.Status', ['Urgent', 'Today', null]);
                        break;
                    case 'Review':
                        $data = $data->whereIn('data.Status', ['Review']);
                        break;
                    case 'Other':
                        $data = $data->whereIn('data.Status', ['Pending', 'Request']);
                        break;
                    case 'Completed':
                        $data = $data->whereIn('data.Status', ['Completed']);
                        break;
                    default:
                        $data = $data->whereIn('data.Status', ['TIDAKFILTER']);
                        break;
                }
            }
            // dd($data->get());
            $data = $this->crudController->list($this->module, $data, $request);
            foreach ($data->data as $row) {
                $row->Action = $this->action($row->Oid);
                if ($mode == 'view') $row->DefaultAction = [
                    'name' => 'Preview',
                    'icon' => 'ArrowUpRightIcon',
                    'type' => 'open_view',
                    'portalget' => "development/table/vueview?code=Task",
                    'get' => "task/{Oid}",
                ];
                else $row->DefaultAction = $this->popup(false);
            }
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            dd($e);
            errjson($e);
        }
    }

    public function presearch(Request $request)
    {
        // return $this->crudController->presearch('coltask');
        return [
            [
                'fieldToSave' => "Project",
                'hideLabel' => true,
                'type' => "combobox",
                'hiddenField'=> 'ProjectName',
                'column' => "1/5",
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
                'hideLabel' => true,
                'type' => "combobox",
                'hiddenField'=> 'UserName',
                'column' => "1/5",
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
                    ['Oid' => '1', 'Name' => 'WS ZUL EK'],
                    ['Oid' => '2', 'Name' => 'DN VJ'],
                    ['Oid' => '3', 'Name' => 'WS ZUL EK DN VJ'],
                ]
            ],
            [
                'fieldToSave' => "Status",
                'hideLabel' => true,
                'type' => "combobox",
                'hiddenField'=> 'StatusName',
                'column' => "1/5",
                'source' => [],
                'store' => "",
                'default' => "Open",
                'source' => [
                    ['Oid' => 'Open', 'Name' => 'Open & Urgent'],
                    ['Oid' => 'Urgent', 'Name' => 'Today & Urgent'],
                    ['Oid' => 'Review', 'Name' => 'Review'],
                    ['Oid' => 'Other', 'Name' => 'Other'],
                    ['Oid' => 'Completed', 'Name' => 'Completed'],
                    ['Oid' => 'All', 'Name' => 'All'],
                ]
            ],
            [
                'fieldToSave' => "Mode",
                'hideLabel' => true,
                'type' => "combobox",
                'hiddenField'=> 'ModeName',
                'column' => "1/5",
                'source' => [],
                'store' => "",
                'default' => "View",
                'source' => [
                    ['Oid' => 'view', 'Name' => 'View Mode'],
                    ['Oid' => 'edit', 'Name' => 'Edit Mode'],
                ]
            ],
            [
                'type' => 'action',
                'column' => '1/5'
            ]
        ];
    }

    public function index(Request $request)
    {
        try {
            $data1 = Task::with('Projects', 'Projects.ProjectObj')->with([
                'User1Obj' => function ($query) {
                    $query->addSelect('Oid', 'Code', 'Name');
                },
                'User2Obj' => function ($query) {
                    $query->addSelect('Oid', 'Code', 'Name');
                },
                'User3Obj' => function ($query) {
                    $query->addSelect('Oid', 'Code', 'Name');
                },
                'CreatedByObj' => function ($query) {
                    $query->addSelect('Oid', 'Code', 'Name');
                },
            ])->limit(40)->whereNull('GCRecord');
            $data2 = Task::with('Projects', 'Projects.ProjectObj')->with([
                'User1Obj' => function ($query) {
                    $query->addSelect('Oid', 'Code', 'Name');
                },
                'User2Obj' => function ($query) {
                    $query->addSelect('Oid', 'Code', 'Name');
                },
                'User3Obj' => function ($query) {
                    $query->addSelect('Oid', 'Code', 'Name');
                },
                'CreatedByObj' => function ($query) {
                    $query->addSelect('Oid', 'Code', 'Name');
                },
            ])->limit(10)->whereNull('GCRecord');
            if ($request->has('User1')) {
                $data1->whereIn('User', $request->query('User1'));
                $data2->whereIn('User', $request->query('User1'));
            }
            if ($request->has('Status')) {
                switch ($request->query('Status')) {
                    case "Working":
                        // $data1->whereNull('ActualEnd')->where('IsStar',0);
                        $data1->whereIn('Status', ['Entry', 'Open', 'Urgent', null]);
                        $data2->whereIn('Status', ['Entry', 'Open', 'Urgent', null]);
                        break;
                    case "Complete":
                        $data1->whereIn('Status', ['Completed']);
                        $data2->whereIn('Status', ['Completed']);
                        // $data1->whereNotNull('ActualEnd');
                        break;
                    case "Pending":
                        $data1->whereIn('Status', ['Pending', 'Postpone', 'Request']);
                        $data2->whereIn('Status', ['Pending', 'Postpone', 'Request']);
                        break;
                }
            }
            if ($request->has('Tags')) {
                $tags = $request->input('Tags');
                $data1->whereHas('Projects', function ($query) use ($tags) {
                    $query->whereIn('Project', $tags);
                });
                $data2->whereHas('Projects', function ($query) use ($tags) {
                    $query->whereIn('Project', $tags);
                });
            }
            $data1 = $data1->orderBy('IsImportant', 'Desc')->orderBy('CreatedAt', 'Desc')->get();
            $data2 = $data2->orderBy('IsImportant', 'Desc')->orderBy('CreatedAt', 'Desc')->get();
            foreach ($data1 as $row) {
                $arr = [];
                foreach ($row->Projects as $detail) if ($detail->Project) $arr[] = $detail->ProjectObj->Name;
                unset($row->Projects);
                $row->Tags = $arr;
                $row->IsCompleted = $row->ActualEnd != null;
            }
            foreach ($data2 as $row) {
                $arr = [];
                foreach ($row->Projects as $detail) if ($detail->Project) $arr[] = $detail->ProjectObj->Name;
                unset($row->Projects);
                $row->Tags = $arr;
                $row->IsCompleted = $row->ActualEnd != null;
            }
            $result = $data1->merge($data2);
            return $result;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    private function showSub($Oid)
    {
        $data = $this->crudController->detail($this->module, $Oid);
        $data->Action = $this->action($data);
        $data->Config = [
            [
                'fieldToSave' => "Title",
                'type' => "inputtext",
                'default' => null,
            ],
            [
                'fieldToSave' => "Description",
                'overrideLabel' => "Description / Cronology / JSON API",
                'type' => "inputeditor",
                'default' => null,
            ],
            [
                'fieldToSave' => "Project",
                'hiddenField' => "ProjectName",
                'type' => "combobox",
                'column' => "1/3",
                'default' => null,
                'source' => "project",
                'onClick' => [
                    "action" => "request",
                    "params" => null,
                    "store" => "data/project",
                ],
            ],
            [
                'fieldToSave' => "TechnicalDatabase",
                'overrideLabel' => "Customer / DB / Login",
                'type' => "inputtext",
                'default' => null,
                'column' => "1/3",
            ],
            [
                'fieldToSave' => "Keyword",
                'overrideLabel' => "Requestor (User)",
                'type' => "inputtext",
                'default' => null,
                'column' => "1/3",
            ],
            [
                'fieldToSave' => "TechnicalAPIUrl",
                'overrideLabel' => "URL API",
                'type' => "inputtext",
                'default' => null,
            ],
            [
                'fieldToSave' => "URL",
                'overrideLabel' => "URL Website",
                'type' => "inputtext",
                'default' => null,
            ],
        ];
        return $data;
    }

    // private function showSub($Oid)
    // {
    //     $data = Task::with('CompanyObj','Logs', 'Images', 'Files')->findOrFail($Oid);
    //     $data->CompanyName = $data->CompanyObj ? $data->CompanyObj->Name : null;

    //     $data->UserFinalName = $data->UserFinalObj ? $data->UserFinalObj->Name : null;
    //     $data->UserName = $data->UserObj ? $data->UserObj->Name : null;
    //     $data->User1Name = $data->User1Obj ? $data->User1Obj->Name : null;
    //     $data->ProjectName = $data->ProjectObj ? $data->ProjectObj->Name : null;
    //     $data->TaskReferenceName = $data->TaskReferenceObj ? $data->TaskReferenceObj->Name : null;
    //     $data->User2Name = $data->User2Obj ? $data->User2Obj->Name : null;
    //     $data->User3Name = $data->User3Obj ? $data->User3Obj->Name : null;
    //     $data->Action = $this->action($Oid);

    //     foreach ($data->Comments as $row) {
    //         $row->UserObj = [
    //             'Oid' => $row->UserObj->Oid,
    //             'Name' => $row->UserObj->Name,
    //             'Image' => $row->UserObj->Image,
    //         ];
    //     }

    //     foreach ($data->Logs as $row) {
    //         $row->User1Name = $row->User1Obj ? $row->User1Obj->Name : null;
    //         $row->User3Name = $row->User3Obj ? $row->User3Obj->Name : null;
    //         $row->User2Name = $row->User2Obj ? $row->User2Obj->Name : null;
    //     }

    //     return $data;
    // }


    public function show(Task $data)
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
                $user = Auth::user();
                $data = $this->crudController->saving($this->module, $request, $Oid, false);

                if (!$data->Oid) {
                    $data->ActualEnd = null;
                    $data->ActualStart = null;
                }
                if (!$data->User && $data->User1) $data->User = $data->User1;
                if (!$data->User1 && $data->User) $data->User1 = $data->User;
                if ($data->Status == '09128d8c-a364-4dc7-bd3b-a2d15d8fefc5') $data->Status = 'Open';

                $project = Project::where('Oid', $data->Project ?: '')->first();
                if (in_array($project->Code, ['Admin', 'Hokindo', 'Trucking', 'POS'])) $data->UserFinal = User::where('Name', 'Vivi')->first()->Oid;
                if (in_array($project->Code, ['TravelVue', 'TravelAdmin'])) $data->UserFinal = User::where('Name', 'Enni')->first()->Oid;
                if (!$data->UserFinal) $data->UserFinal = User::where('Name', 'Vivi')->first()->Oid;
                if (!$data->User) {
                    $data->User = User::where('Name', 'Unassigned')->first()->Oid;
                    $data->User1 = User::where('Name', 'Unassigned')->first()->Oid;
                }
                $data->save();
                $this->taskLog($data, $user->Name . " task is created");
                $this->publicPostController->sync($data, 'Task');

                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('Task'); //rolepermission
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

    public function destroy(Task $data)
    {
        try {

            DB::transaction(function () use ($data) {
                //delete
                $delete = PublicApproval::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = Image::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicComment::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicFile::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicPost::where('Oid', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = TaskLog::where('Task', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = TaskProject::where('Task', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

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

    private function action($oid)
    {
        $action = [
            [
                "name" => "Preview",
                "icon" => "EyeIcon",
                "type" => "open_view",
                "portalget" => "development/table/vueview?code=Task",
                "get" => "task/{Oid}"
            ],
            $this->popup(false),
            [
                "name" => "Change Status",
                "icon" => "ActivityIcon",
                "type" => "global_form",
                "showModal" => false,
                "post" => "task/status/{Oid}",
                "afterRequest" => "apply",
                "form" => [
                    [
                        "fieldToSave" => "Status",
                        "hideLabel" => true,
                        "type" => "combobox",
                        "store" => "",
                        "default" => "Open",
                        "source" => [
                            [
                                "Oid" => "Open",
                                "Name" => "Open"
                            ],
                            [
                                "Oid" => "Today",
                                "Name" => "Today"
                            ],
                            [
                                "Oid" => "Urgent",
                                "Name" => "Urgent"
                            ],
                            [
                                "Oid" => "Request",
                                "Name" => "Request"
                            ],
                            [
                                "Oid" => "Pending",
                                "Name" => "Pending"
                            ],
                            [
                                "Oid" => "Review",
                                "Name" => "Review"
                            ],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Change User',
                'icon' => 'PlusIcon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => 'task/{Oid}',
                'post' => 'task/{Oid}',
                'afterRequest' => "apply",
                'config' => 'task/field?type=user',
            ],
            [
                "name" => "Complete Task",
                "icon" => "ActivityIcon",
                "type" => "confirm",
                "showModal" => false,
                "post" => "task/end/{Oid}",
                "afterRequest" => "apply",
            ],
            [
                'name' => 'Seperator',
                'type' => 'seperator',
            ],
            [
                "name" => "Edit in Detail",
                "icon" => "EditIcon",
                "type" => "open_form",
                "url" => "task/form?item={Oid}",
                "afterRequest" => "apply"
            ],
            [
                "name" => "Delete",
                "icon" => "TrashIcon",
                "type" => "delete"
            ],
            [
                'name' => 'Edit GlobalForm2',
                'icon' => 'PlusIcon',
                'type' => 'global_form2',
                'showModal' => false,
                'post' => 'task/{Oid}',
                'get' => 'task/{Oid}',
                'afterRequest' => "apply",
            ]
        ];
        return $action;
    }

    private function taskLog(Task $data, $description)
    {
        $log = new TaskLog();
        $log->Task = $data->Oid;
        $log->User1 = Auth::user()->Oid;
        $log->Description = $description;
        $log->save();

        $user = Auth::user()->Name;
        $data->HistoryLog = $data->HistoryLog . PHP_EOL . $user . ': ' . $description . ' - ' . now();
        $data->save();
    }

    public function statusChange(Request $request, Task $data)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF
        switch ($request->Status) {
            case 'Open':
                return $this->statusOpen($data);
            case 'Urgent':
                return $this->statusUrgent($data);
            case 'Today':
                return $this->statusToday($data);
            case 'Review':
                return $this->statusReview($data);
            case 'Pending':
                return $this->statusPending($data);
            case 'Request':
                return $this->statusRequest($data);
            case 'End':
                return $this->statusEnd($data);
        }
    }

    public function statusReview(Task $data)
    {
        try {
            DB::transaction(function () use (&$data) {
                $user = Auth::user();
                $this->taskLog($data, $user->Name . " change status to Review");
                $data->Status = 'Review';
                $data->save();
            });
            $data = $this->showSub($data->Oid);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusToday(Task $data)
    {
        try {
            DB::transaction(function () use (&$data) {
                $user = Auth::user();
                $this->taskLog($data, $user->Name . " change status to Today");
                $data->Status = 'Today';
                $data->save();
            });
            $data = $this->showSub($data->Oid);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusUrgent(Task $data)
    {
        try {
            DB::transaction(function () use (&$data) {
                $user = Auth::user();
                $this->taskLog($data, $user->Name . " change status to Urgent");
                $data->Status = 'Urgent';
                $data->save();
            });
            $data = $this->showSub($data->Oid);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusPending(Task $data)
    {
        try {
            DB::transaction(function () use (&$data) {
                $user = Auth::user();
                $this->taskLog($data, $user->Name . " change status to Pending ");
                $data->Status = 'Pending';
                $data->save();
            });
            $data = $this->showSub($data->Oid);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusOpen(Task $data)
    {
        try {
            DB::transaction(function () use (&$data) {
                $user = Auth::user();
                $this->taskLog($data, $user->Name . " change status to Open");
                $data->Status = 'Open';
                $data->save();
            });
            $data = $this->showSub($data->Oid);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusRequest(Task $data)
    {
        try {
            DB::transaction(function () use (&$data) {
                $user = Auth::user();
                $this->taskLog($data, $user->Name . " change status to Request");
                $data->Status = 'Request';
                $data->save();
            });
            $data = $this->showSub($data->Oid);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusEnd(Task $data)
    {
        try {
            DB::transaction(function () use (&$data) {
                $nextUser = null;
                if ($data->User == $data->UserFinal) $nextUser = null;
                elseif ($data->User == $data->User1) $nextUser = $data->User2 ? $data->User2 : $data->UserFinal;
                elseif ($data->User == $data->User2) $nextUser = $data->User3 ? $data->User3 : $data->UserFinal;
                elseif ($data->User == $data->User3) $nextUser = $data->UserFinal ? $data->UserFinal : null;
                elseif (!$data->UserFinal) $nextUser = null;

                if ($nextUser) $nextUser = User::findOrFail($nextUser);

                $this->taskLog(
                    $data,
                    "ended from " . ($data->UserObj ? $data->UserObj->Name : "") . ($nextUser ? " to " . $nextUser->Name : "")
                );

                // $data->ActualEnd = now()->addHours(company_timezone())->toDateTimeString();
                // if ($data->ActualStart == null) $data->ActualStart = $data->ActualEnd;
                // $hours = floor((strtotime($data->ActualEnd) - strtotime($data->ActualStart)) / 60 / 60);
                // $hours = $hours > 0 ? $hours : 1;
                // $days = $hours > 8 ? floor($hours / 8) : 0;
                // $hours = $days > 0 ? $days % 8 : $hours;
                // $data->ActualDurationDay = $days;
                // $data->ActualDurationHour = $hours;
                if (!$nextUser) $data->Status = 'Completed';
                else $data->User = $nextUser->Oid;
                $data->save();
            });
            $data = $this->showSub($data->Oid);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusStart(Task $data)
    {
        try {
            DB::transaction(function () use (&$data) {
                $data->ActualStart = now()->addHours(company_timezone())->toDateTimeString();
                $data->Status = 'Started';
                $data->save();
            });
            $data = Task::findOrFail($data->Oid);
            $this->taskLog($data, "started");
            return $data;
            // return response()->json(
            //     $data, Response::HTTP_NO_CONTENT
            // );

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function saveEdit(Request $request, $Oid = null)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

        try {
            if (!$Oid) $data = new Task();
            else $data = Task::with('Projects', 'Projects.ProjectObj', 'User1Obj', 'User2Obj', 'User3Obj', 'CreatedByObj')->findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                if (!$data->Oid) {
                    $request->ActualEnd = null;
                    $request->ActualStart = null;
                }
                if (isset($request->User1)) if ($request->User1 == '') $request->User1 = null;
                if (isset($request->User2)) if ($request->User2 == '') $request->User2 = null;
                if (isset($request->User3)) if ($request->User3 == '') $request->User3 = null;
                $userOld = isset($data->User1) ? $data->User1Obj : null;
                $disabled = ['Oid', 'HistoryLog', 'Projects', 'GCRecord', 'OptimisticLock', 'Image1', 'Image2', 'Image3', 'Image4', 'Image5', 'CreatedAt', 'UpdatedAt', 'CreatedAtUTC', 'UpdatedAtUTC', 'CreatedBy', 'UpdatedBy'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                // $data->CreatedBy = 'Completed';
                if ($data->ActualDateEnd != null) $data->Status = 'Completed';
                elseif ($data->ActualDateStart != null) $data->Status = 'Started';
                else $data->Status = 'Open';
                if (isset($request->Image1)) if (isset($request->Image1->base64)) $data->Image1 = $this->fileCloudService->uploadImage($request->Image1, $data->Image1);
                if (isset($request->Image2)) if (isset($request->Image2->base64)) $data->Image2 = $this->fileCloudService->uploadImage($request->Image2, $data->Image2);
                if (isset($request->Image3)) if (isset($request->Image3->base64)) $data->Image3 = $this->fileCloudService->uploadImage($request->Image3, $data->Image3);
                if (isset($request->Image4)) if (isset($request->Image4->base64)) $data->Image4 = $this->fileCloudService->uploadImage($request->Image4, $data->Image4);
                if (isset($request->Image5)) if (isset($request->Image5->base64)) $data->Image5 = $this->fileCloudService->uploadImage($request->Image5, $data->Image5);

                $data->save();
                $user = Auth::user();
                if (isset($userOld)) if ($userOld->Oid != $data->User1) $this->taskLog($data, $user->Name . " change user from ".$userOld->Name);

                if ($data->Projects()->count() != 0) {
                    foreach ($data->Projects as $rowdb) {
                        $found = false;
                        foreach ($request->Projects as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) $found = true;
                            }
                        }
                        if (!$found) {
                            $detail = TaskProject::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }
                if ($request->Projects) {
                    $details = [];
                    $disabled = ['Oid', 'Task', 'GCRecord', 'OptimisticLock', 'CreatedAt', 'UpdatedAt', 'CreatedAtUTC', 'UpdatedAtUTC', 'CreatedBy', 'UpdatedBy'];
                    foreach ($request->Projects as $row) {
                        if (isset($row->Oid)) {
                            $detail = TaskProject::findOrFail($row->Oid);
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;
                                $detail->{$field} = $row->{$field};
                            }
                            $detail->save();
                        } else {
                            $arr = [];
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;
                                $arr = array_merge($arr, [
                                    $field => $row->{$field},
                                ]);
                            }
                            $details[] = new TaskProject($arr);
                        }
                    }
                    $data->Projects()->saveMany($details);
                    $data->load('Projects');

                    $data = Task::with('Projects', 'Projects.ProjectObj', 'User1Obj', 'User2Obj', 'User3Obj', 'Logs', 'Logs.User1Obj')->findOrFail($data->Oid);
                    $data->fresh();
                }
                $this->taskLog($data, "Updated");
                if (!$data) throw new \Exception('Data is failed to be saved');
            });

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

    public function listChart(Request $request)
    {
        if (!$request->has('type')) return null;
        $data = TaskTemp::whereNull("GCRecord");
        $data = $data->whereRaw("DateStart >= '" . $request->input('datestart') . "'");
        $data = $data->whereRaw("DateStart <= '" . $request->input('dateend') . "'");
        $tasks = [];
        $links = [];
        foreach ($data as $row) {
            $tasks[] = [
                "id" => $row->Oid,
                "text" => $row->Name,
                "start_date" => $row->DateStart,
                "duration" => $row->Duration,
                "progress" => $row->Progress,
            ];
            if ($row->TaskReference) $links[] = [
                "id" => $row->Oid,
                "source" => $row->Oid,
                "target" => $row->TaskReference,
                "type" => 0,
            ];
        }
        return [
            "tasks" => [
                "data" => $tasks,
                "links" => $links,
            ],
        ];
    }
}
