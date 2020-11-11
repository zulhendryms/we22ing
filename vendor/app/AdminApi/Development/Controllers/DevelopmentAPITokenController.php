<?php

namespace App\AdminApi\Development\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class DevelopmentAPITokenController extends Controller
{
    private $crudController;
    private $dbConnection;
    public function __construct()
    {
        $this->dbConnection = DB::connection('server');
        $this->crudController = new CRUDDevelopmentController();
    }

    public function fields()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w' => 0, 'r' => 0, 't' => 'text', 'n' => 'Oid'];
        $fields[] = ['w' => 150, 'r' => 0, 't' => 'text', 'n' => 'Code'];
        $fields[] = ['w' => 300, 'r' => 0, 't' => 'text', 'n' => 'Name'];
        $fields[] = ['w' => 200, 'r' => 0, 't' => 'text', 'n' => 'LastExecutedDate'];
        $fields[] = ['w' => 200, 'r' => 0, 't' => 'text', 'n' => 'NextExecutionDate'];
        return $fields;
    }


    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields(), false, true);
        $fields[0]['cellRenderer'] = 'actionCell';
        $fields[0]['topButton'] = [ $this->action(true)[0] ];
        return $fields;
    }

    public function list(Request $request)
    {
        $criteria = null;
        if ($request->has('search')) {
            $search = $request->input('search');
            $criteria = "WHERE Name LIKE '%{$search}%' OR Code LIKE '%{$search}%'";
        }
        return $this->subList($criteria, false);
    }

    private function subList($where = null, $noAction = true)
    {
        $query = "SELECT Oid, Code, Name, LastExecutedDate, NextExecutionDate FROM ezb_server.apitoken " . $where;
        $data = $this->dbConnection->select(DB::raw($query));
        if (!$noAction) foreach ($data as $row) {
            $row->DefaultAction = $this->action(false)[0];
            $row->Action = $this->action();
        }
        return $data;
    }

    public function presearch(Request $request)
    {
        return null;
    }

    public function index(Request $request)
    {
        return null;
    }

    public function listLog($Oid)
    {
        // RIGHT(l.APIUrl,LOCATE('/',REVERSE(l.APIUrl)))
        $query = "SELECT DATE_FORMAT(l.CreatedAt, '%e-%b %H:%i') AS Date, Type, l.APIMethod AS Method, l.APIUrl AS Url
            FROM apilistlog l
            WHERE l.APIToken='".$Oid."' ORDER BY CreatedAt DESC LIMIT 20";
        $data = $this->dbConnection->select(DB::raw($query));
        return $data;
    }

    private function action($isAdd = false)
    {
        return [
            [
                'name' => ($isAdd ? 'Create' : 'Edit').' API Setting',
                'icon' => 'Edit2Icon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => $isAdd ? null : 'development/apitoken/{Oid}',
                'portalpost' => 'development/apitoken/save' . ($isAdd ? '' : '/{Oid}'),
                'afterRequest' => "apply",
                'form' => [
                    [
                        'fieldToSave' => 'Code',
                        'type' => 'inputtext',
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'Name',
                        'type' => 'inputtext',
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'IsSingleAPI',
                        'type' => 'checkbox',
                        'default' => null
                    ],
                    [
                        'fieldToSave' => "Type",
                        'type' => "combobox",
                        'store' => "",
                        "hideWhen" => [
                            ["link" => "IsSingleAPI", "condition" => false]
                        ],
                        'source' => [
                            ['Oid' => 'Single', 'Name' => 'Single'],
                            ['Oid' => 'Multi', 'Name' => 'Multi'],
                            ['Oid' => 'Email', 'Name' => 'Email'],
                        ]
                    ],
                    [
                        'fieldToSave' => "SingleAPIMethod",
                        'type' => "combobox",
                        'store' => "",
                        "hideWhen" => [
                            ["link" => "IsSingleAPI", "condition" => false]
                        ],
                        'source' => [
                            ['Oid' => 'get', 'Name' => 'GET'],
                            ['Oid' => 'post', 'Name' => 'POST'],
                            ['Oid' => 'put', 'Name' => 'PUT'],
                            ['Oid' => 'delete', 'Name' => 'DELETE'],
                        ]
                    ],
                    [
                        'fieldToSave' => "SingleAPIUrl",
                        'type' => "inputtext",
                        "hideWhen" => [
                            ["link" => "IsSingleAPI", "condition" => false],
                        ]
                    ],
                    [
                        'fieldToSave' => "SingleAPIParam",
                        'type' => "inputarea",
                        "hideWhen" => [
                            ["link" => "IsSingleAPI", "condition" => false],
                        ]
                    ],
                    [
                        'fieldToSave' => "EmailSubject",
                        'type' => "inputtext",
                        "hideWhen" => [
                            ["link" => "Type", "condition" => "Single"]
                        ]
                    ],
                    [
                        'fieldToSave' => "EmailTo",
                        'type' => "inputtext",
                        "hideWhen" => [
                            ["link" => "Type", "condition" => "Single"]
                        ]
                    ],
                    [
                        'fieldToSave' => "EmailBody",
                        'type' => "inputarea",
                        "hideWhen" => [
                            ["link" => "Type", "condition" => "Single"]
                        ]
                    ],
                ]
            ],
            [
                'name' => 'View API Log',
                'icon' => 'PrinterIcon',
                'type' => 'open_grid',
                'get' => 'development/apitoken/log/{Oid}'
            ],
            [
                'name' => 'Seperator',
                'type' => 'seperator',
            ],
            [
                'name' => 'View API List',
                'icon' => 'BookOpenIcon',
                'type' => 'open_form',
                'url' => "development/apilist?APIToken={Oid}&APITokenName={Name}",
            ], 
            [
                'name' => ($isAdd ? 'Create' : 'Edit').' Interval',
                'icon' => 'Edit2Icon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => $isAdd ? null : 'development/apitoken/{Oid}',
                'portalpost' => 'development/apitoken/save' . ($isAdd ? '' : '/{Oid}'),
                'afterRequest' => "apply",
                'form' => [
                    [
                        'fieldToSave' => 'DateFrom',
                        'type' => 'inputdate',
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'DateUntil',
                        'type' => 'inputdate',
                        'default' => null
                    ],
                    [
                        'fieldToSave' => "IntervalType",
                        'type' => "combobox",
                        'store' => "",
                        'source' => [
                            ['Oid' => 'Year', 'Name' => 'Year'],
                            ['Oid' => 'Month', 'Name' => 'Month'],
                            ['Oid' => 'Week', 'Name' => 'Week'],
                            ['Oid' => 'Day', 'Name' => 'Day'],
                            ['Oid' => 'Hour', 'Name' => 'Hour'],
                            ['Oid' => 'Minute', 'Name' => 'Minute'],
                        ]
                    ],
                    [
                        'fieldToSave' => 'Interval',
                        'type' => 'inputtext',
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'IsActive',
                        'type' => 'checkbox',
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'LastExecutedDate',
                        'type' => 'inputtext',
                        'disabled' => true,
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'NextExecutionDate',
                        'type' => 'inputtext',
                        'disabled' => true,
                        'default' => null
                    ],
                ]
            ],
            [
                'name' => 'Edit Token',
                'icon' => 'Edit2Icon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => $isAdd ? null : 'development/apitoken/{Oid}',
                'portalpost' => 'development/apitoken/save' . ($isAdd ? '' : '/{Oid}'),
                'afterRequest' => "apply",
                'form' => [
                    [
                        'fieldToSave' => 'Token',
                        'type' => 'inputarea',
                        'default' => null
                    ]
                ]
            ],
            [
                'name' => 'Seperator',
                'type' => 'seperator',
            ],
            [
                'name' => 'Reset Next Execution Date',
                'icon' => 'AlertTriangleIcon',
                'portalpost' => 'development/apitoken/reset/{Oid}',
                'type' => 'global_form',
                'showModal' => false,
                'afterRequest' => "apply",
                'form' => [
                    [
                        'fieldToSave' => 'Hour',
                        'type' => 'inputtext',
                        'validationParams' => 'money|required',
                        'default' => 13
                    ],
                    [
                        'fieldToSave' => 'Minute',
                        'type' => 'inputtext',
                        'validationParams' => 'money|required',
                        'default' => 0
                    ]
                ]
            ],
            [
                'name' => 'Delete',
                'icon' => 'TrashIcon',
                'type' => 'confirm',
                'delete' => 'development/apitoken/delete/{Oid}',
            ]
        ];
    }

    public function show($data)
    {
        if ($data == 'undefined') return null;
        $query = "SELECT a.*
            FROM apitoken a
            WHERE a.Oid='{$data}'";
        $data = $this->dbConnection->select(DB::raw($query))[0];
        $data->Action = $this->action(false);
        $data = collect($data);
        return $data;
    }

    public function destroy($data)
    {
        $query = "DELETE FROM apitoken WHERE Oid='{$data}'";
        $data = $this->dbConnection->select(DB::delete($query));
    }
}
