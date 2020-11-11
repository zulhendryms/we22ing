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

class DevelopmentFieldController extends Controller
{
    private $crudController;
    private $dbConnection;
    public function __construct()
    {
        $this->dbConnection = DB::connection('server');
        $this->crudController = new CRUDDevelopmentController();
    }

    public function fields() {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'Oid'];
        $fields[] = ['w'=> 200, 'r'=>0, 't'=>'text', 'n'=>'Code'];
        $fields[] = ['w'=> 80, 'r'=>0, 't'=>'text', 'n'=>'Act'];
        $fields[] = ['w'=> 80, 'r'=>0, 't'=>'text', 'n'=>'Seq'];
        $fields[] = ['w'=> 80, 'r'=>0, 't'=>'text', 'n'=>'Col'];
        $fields[] = ['w'=> 80, 'r'=>0, 't'=>'text', 'n'=>'Glo'];
        $fields[] = ['w'=> 400, 'r'=>0, 't'=>'text', 'n'=>'Description'];
        return $fields;
    }

    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields(),false,true);
        $fields[0]['cellRenderer'] = 'actionCell';
        $fields[0]['topButton'] = $this->actionTop();
        return $fields;
    }

    public function list(Request $request)
    {
        $criteria = null;
        if (!$request->has('APITable')) return null;
        $criteria = "WHERE tbf.APITable='".$request->input('APITable')."'";
        if ($request->has('search')){
            $search = $request->input('search');
            $criteria = $criteria." AND (tbf.Name LIKE '%{$search}%' OR tbf.Code LIKE '%{$search}%')";
        }
        return $this->subList($criteria, false);
    }
    private function subList ($where = null, $noAction = true) {
        // $query = "SELECT tbf.Oid, CONCAT(tbf.Code, ' (',tbf.FieldType,')') AS Code,
        //         CASE WHEN tbf.IsActive = 1 THEN 'Y' ELSE 'N' END AS Act
        //         FROM ezb_server.apitable tb
        //         LEFT OUTER JOIN ezb_server.apitablefield tbf ON tbf.APITable = tb.Oid 
        //         {$where}
        //         ORDER BY CASE WHEN tbf.IsActive = 1 THEN 0 ELSE 100 END, tbf.Sequence";
        $query = "SELECT tbf.Oid, CONCAT(tbf.Code, ' (',tbf.FieldType,')') AS Code,
                CASE WHEN tbf.IsActive = 1 THEN 'Y' ELSE 'N' END AS Act, 
                tbf.Sequence AS Seq, tbf.LayoutColumn AS Col,   
                CASE WHEN tbf.IsGlobalSetting = 1 THEN 'Y' ELSE 'N' END AS Glo,
                CONCAT(
                    CASE WHEN LENGTH(IFNULL(tbf.LayoutTab,'')) < 1 THEN '' ELSE CONCAT('TAB: ',tbf.LayoutTab,'; ') END,
                    CASE WHEN LENGTH(IFNULL(tbf.LayoutGroup,'')) < 1 THEN '' ELSE CONCAT('GROUP: ',tbf.LayoutGroup,'; ') END,
                    CASE WHEN LENGTH(IFNULL(tbf.OnChange,'')) < 1 THEN '' ELSE 'OnChange; ' END,
                CASE WHEN LENGTH(IFNULL(tbf.DefaultValue,'')) < 1 THEN '' ELSE 'Def; ' END,
                CASE WHEN LENGTH(IFNULL(tbf.OnHideWhen,'')) < 1 THEN '' ELSE 'HideWhen; ' END
                ) AS Description
                FROM ezb_server.apitable tb
                LEFT OUTER JOIN ezb_server.apitablefield tbf ON tbf.APITable = tb.Oid 
                {$where}
                ORDER BY CASE WHEN tbf.IsActive = 1 THEN 0 ELSE 100 END, tbf.Sequence";
        $data = $this->dbConnection->select(DB::raw($query));
        
        if (!$noAction) foreach ($data as $row) {
            $row->DefaultAction = $this->action()[0];
            $row->Action = $this->action();
        }
        return $data;
    }

    public function presearch(Request $request) {
        return [
            [
                'fieldToSave'=> 'APITable',
                'overrideLabel'=> 'Choose Table',
                'column' => '1/3',
                'type' => "autocomplete",
                'default' => null,
                'source' => [],
                'store' => "development/table/autocomplete",
                'params' => [
                    'type' => 'combo',
                    'term' => ''
                ],
                'hiddenField' => 'APITableName'
            ],
            [
                'type' => 'action',
                'column' => '1/3'
            ]
        ];
    }

    public function index(Request $request)
    {
        return null;
    }

    public function field (Request $request) {
        if ($request->input('type') == 'field') {
            return [
                [
                    'fieldToSave' => "APITable",
                    'overrideLabel' => "Choose Parent Table",
                    'type' => "autocomplete",
                    'default' => [
                        "Oid" => "{Url.APITable}",
                        "Name" => "{Url.APITableName}",
                    ],
                    'source' => [],
                    'store' => "development/table/autocomplete",
                    'hideWhen' => 'edit',
                    'params' => [
                        'type' => 'combo',
                        'term' => ''
                    ]
                ],
                [
                    'fieldToSave' => "Sequence",
                    'type' => "inputtext",
                    'column' => '1/3'
                ],
                [
                    'fieldToSave' => "Code",
                    'type' => "inputtext",
                    'column' => '1/3',
                    'onChange'=> ["link"=>"Name"]
                ],
                [
                    'fieldToSave' => "Name",
                    'type' => "inputtext",
                    'column' => '1/3'
                ],
                [
                    'fieldToSave' => "FieldType",
                    'overrideLabel' => "Choose Field Type",
                    'type' => "combobox",
                    'column' => '1/4',
                    'store' => "",
                    'source' => [
                        ['Oid' => 'bit', 'Name' => 'bit'],
                        ['Oid' => 'char', 'Name' => 'char'],
                        ['Oid' => 'date', 'Name' => 'date'],
                        ['Oid' => 'datetime', 'Name' => 'datetime'],
                        ['Oid' => 'timestamp', 'Name' => 'timestamp'],
                        ['Oid' => 'double', 'Name' => 'double'],
                        ['Oid' => 'smallint', 'Name' => 'smallint'],
                        ['Oid' => 'int', 'Name' => 'int'],
                        ['Oid' => 'bigint', 'Name' => 'bigint'],
                        ['Oid' => 'text', 'Name' => 'text'],
                        ['Oid' => 'longtext', 'Name' => 'longtext'],
                        ['Oid' => 'mediumtext', 'Name' => 'mediumtext'],
                        ['Oid' => 'varchar', 'Name' => 'varchar'],
                    ],
                    'default' => 'varchar',
                ],
                [
                    'fieldToSave' => "APITableCombo",
                    'overrideLabel' => "Table Combo Source",
                    'type' => "autocomplete",
                    'column' => '1/2',
                    'default' => null,
                    'source' => [],
                    'store' => "development/table/autocomplete",
                    'params' => [
                        'type' => 'combo',
                        'term' => ''
                    ]
                ],
                [ 
                    'fieldToSave' => "MaxCharacter",
                    'overrideLabel' => "Size / Max Char",
                    'type' => "inputtext",
                    'default' => 30,
                    'column' => '1/5'
                ],
                [
                    'fieldToSave' => 'IsGlobalSetting',
                    'type' => 'checkbox',
                    'column' => '1/4',
                    'default' => false,
                ],
                [
                    'fieldToSave' => 'IsActive',
                    'type' => 'checkbox',
                    'column' => '1/4',
                    'default' => false,
                ],
                [ 
                    'fieldToSave' => "IsImage",
                    'type' => "checkbox",
                    'column' => '1/4',
                    'default' => false,
                ],
                [ 
                    'fieldToSave' => "LayoutColumn",
                    'overrideLabel' => "Column (Layout)",
                    'type' => "inputtext",
                    'default' => 2,
                    'column' => '1/4'
                ],
                [
                    'fieldToSave' => "Width",
                    'type' => "inputtext",
                    'column' => '1/4',
                    "hideWhen" => [
                        ["link" => "IsGlobalSetting", "condition" => true]
                    ],
                    'hide' => true,
                ],
                [
                    'fieldToSave' => 'IsListShowPrimary',
                    'overrideLabel' => "IsShowAtList",
                    'type' => 'checkbox',
                    'column' => '1/4',
                    "hideWhen" => [
                        ["link" => "IsGlobalSetting", "condition" => true]
                    ],
                    'hide' => true,
                    'default' => false,
                ],
                [
                    'fieldToSave' => 'IsDisabled',
                    'type' => 'checkbox',
                    'column' => '1/4',
                    "hideWhen" => [
                        ["link" => "IsGlobalSetting", "condition" => true]
                    ],
                    'hide' => true,
                ],
                [
                    'fieldToSave' => 'IsRequired',
                    'type' => 'checkbox',
                    'column' => '1/4',
                    "hideWhen" => [
                        ["link" => "IsGlobalSetting", "condition" => true]
                    ],
                    'hide' => true,
                    'default' => false,
                ],
            ];
        } elseif ($request->input('type') == 'fieldlayout') {            
            return [
                [
                    'fieldToSave' => "LayoutColumn",
                    'overrideLabel' => "Column",
                    'type' => "inputtext",
                    'column' => '1/3'
                ],
                [
                    'fieldToSave' => "LayoutTab",
                    'overrideLabel' => "Name of Tab",
                    'type' => "inputtext",
                    'column' => '1/3'
                ],
                [
                    'fieldToSave' => "LayoutGroup",
                    'overrideLabel' => "Name of Group",
                    'type' => "inputtext",
                    'column' => '1/3'
                ],
                [
                    'fieldToSave' => "IsHideInput",
                    'overrideLabel' => "Hide Input",
                    'type' => "checkbox",
                    'column' => '1/4',
                    'default' => false,
                ],
                [
                    'fieldToSave' => "IsActive",
                    'overrideLabel' => "Is Active",
                    'type' => "checkbox",
                    'default' => false,
                    'column' => '1/4'
                ],
                [
                    'fieldToSave' => "IsListShowPrimary",
                    'overrideLabel' => "List Show",
                    'type' => "checkbox",
                    'default' => false,
                    'column' => '1/4'
                ],
            ];
        } elseif ($request->input('type') == 'fieldcoding') {
            return [
                [
                    'fieldToSave' => "DefaultValue",
                    'overrideLabel' => "Coding: Default Value (0 / NOW / FORMULA)",
                    'type' => "inputarea",
                ],
                [
                    'fieldToSave' => "OnHideWhen",
                    'overrideLabel' => "Coding: Hide When",
                    'type' => "inputarea",
                ],
                [
                    'fieldToSave' => "OnChange",
                    'overrideLabel' => "Coding: On Change",
                    'type' => "inputarea",
                ],
                [
                    'fieldToSave' => "OnChangeCalculated",
                    'overrideLabel' => "Coding: On Change Calculated (Formula)",
                    'type' => "inputarea",
                ],
                [
                    'fieldToSave' => "DisabledWhen",
                    'overrideLabel' => "Coding: Disabled When",
                    'type' => "inputarea",
                ],
                [
                    'fieldToSave' => "ComboParams",
                    'overrideLabel' => "Coding: Combo API Parameter (FOR COMBO ONLY)",
                    'type' => "inputarea",
                ],
                [
                    'fieldToSave' => "ComboSourceManual",
                    'overrideLabel' => "Coding: Combo Source Manual",
                    'type' => "inputarea",
                ],
            ];
        }
    }

    private function actionForm($isAdd = false) {
        return 
        [
            'name' => ($isAdd ? 'Create' : 'Edit').' Field',
            'icon' => ($isAdd ? 'PlusCircleIcon' : 'EditIcon'),
            'type' => 'global_form',
            'showModal' => false,
            'get' => $isAdd ? null : 'development/field/{Oid}',
            'portalpost' => 'development/table/savefield'.($isAdd ? '' : '/{Oid}'),
            'afterRequest' => "apply",
            'config' => 'development/field/field?type=field'
        ];
    }

    private function actionTable($isAdd = false) {        
        return [
            'name' => ($isAdd ? 'Create' : 'Edit').' Table',
            'icon' => ($isAdd ? 'PlusCircleIcon' : 'EditIcon'),
            'type' => 'global_form',
            'showModal' => false,
            'get' => $isAdd ? null : 'development/table/{Url.APITable}',
            'portalpost' => 'development/table/save' . ($isAdd ? '' : '/{Url.APITable}'),
            'afterRequest' => "apply",
            'form' => [
                [
                    'fieldToSave' => "Code",
                    'type' => "inputtext",
                    'column' => '1/2'
                ],
                [
                    'fieldToSave' => "Name",
                    'type' => "inputtext",
                    'column' => '1/2'
                ],
                [
                    'fieldToSave' => "FormType",
                    'overrideLabel' => "Type",
                    'type' => "combobox",
                    'store' => "",
                    'source' => [
                        ['Oid' => 'Auto', 'Name' => 'Master Popup (Auto)'],
                        ['Oid' => 'Transaction', 'Name' => 'Transaction (FormTrans)'],
                        ['Oid' => 'Detail', 'Name' => 'Detail (Normal)'],
                        ['Oid' => 'DetailBatch', 'Name' => 'Detail InLine Edit Save Batch'],
                    ]
                ],
                [
                    'fieldToSave' => "APITableParent",
                    'overrideLabel' => "Choose Parent Table Combo (only detail table)",
                    'type' => "autocomplete",
                    'default' => null,
                    'source' => [],
                    'store' => "development/table/autocomplete",
                    'params' => [
                        'type' => 'combo',
                        'term' => ''
                    ]
                ],
                [
                    'fieldToSave' => "APITableParentRelationshipName",
                    'overrideLabel' => "Parent Relationship Name (only detail table), ex: Details",
                    'type' => "inputtext"
                ],
                [
                    'fieldToSave' => "APITableGroup",
                    'overrideLabel' => "Laravel Namespace",
                    'type' => "combobox",
                    'store' => "",
                    'source' => $this->sourceNameSpace()
                ],
                [
                    'fieldToSave' => "ComboStoreSource",
                    'overrideLabel' => "Combo Source API (can put data/)",
                    'type' => "inputtext"
                ],
                [
                    'fieldToSave' => "Combo1",
                    'overrideLabel' => "Field for Combo Display (leave blank for field 'Name')",
                    'type' => "inputtext"
                ],
                [
                    'fieldToSave' => "IsActive",
                    'overrideLabel' => "Is Active?",
                    'type' => "checkbox",
                    'default' => false,
                    'column' => '1/2'
                ],
                [
                    'fieldToSave' => "IsComboAutoComplete",
                    'overrideLabel' => "Is Combo AutoComplete?",
                    'type' => "checkbox",
                    'default' => false,
                    'column' => '1/2'
                ],
                [
                    'fieldToSave' => "GlobalTitle",
                    'overrideLabel' => "Global Setting: Override Title / Label",
                    'type' => "inputtext",
                ],
                [
                    'fieldToSave' => "GlobalWidth",
                    'overrideLabel' => "Global: Width",
                    'type' => "inputtext",
                    'column' => '1/3'
                ],
                [
                    'fieldToSave' => "GlobalIsDisabled",
                    'overrideLabel' => "Global: Disabled",
                    'type' => "checkbox",
                    'default' => false,
                    'column' => '1/3'
                ],
                [
                    'fieldToSave' => "GlobalIsRequired",
                    'overrideLabel' => "Global: Required",
                    'type' => "checkbox",
                    'default' => false,
                    'column' => '1/3'
                ],
            ]
        ];
    }
    private function sourceNameSpace() {
        return [
            ['Oid' => 'Accounting', 'Name' => 'Accounting'],
            ['Oid' => 'Chart', 'Name' => 'Chart'],
            ['Oid' => 'Chat', 'Name' => 'Chat'],
            ['Oid' => 'Collaboration', 'Name' => 'Collaboration'],
            ['Oid' => 'Ferry', 'Name' => 'Ferry'],
            ['Oid' => 'HumanResource', 'Name' => 'HumanResource'],
            ['Oid' => 'Internal', 'Name' => 'Internal'],
            ['Oid' => 'Master', 'Name' => 'Master'],
            ['Oid' => 'Pub', 'Name' => 'Pub'],
            ['Oid' => 'POS', 'Name' => 'POS'],
            ['Oid' => 'Trading', 'Name' => 'Trading'],
            ['Oid' => 'Travel', 'Name' => 'Travel'],
            ['Oid' => 'Trucking', 'Name' => 'Trucking'],
        ];
    }

    private function actionTop() {
        return [
            $this->actionForm(true),
            $this->actionTable(true),
            [
                'name' => 'Quick Add Field',
                'icon' => 'PlusIcon',
                'type' => 'global_form',
                'showModal' => false,
                'portalpost' => 'development/table/savefield/{Url.APITable}',
                'form' => [
                    [
                        'fieldToSave' => "FieldType",
                        'overrideLabel' => "Choose Field Type",
                        'type' => "combobox",
                        'column' => '1/4',
                        'store' => "",
                        'source' => [
                            ['Oid' => 'bit', 'Name' => 'bit'],
                            ['Oid' => 'char', 'Name' => 'char'],
                            ['Oid' => 'date', 'Name' => 'date'],
                            ['Oid' => 'datetime', 'Name' => 'datetime'],
                            ['Oid' => 'timestamp', 'Name' => 'timestamp'],
                            ['Oid' => 'double', 'Name' => 'double'],
                            ['Oid' => 'smallint', 'Name' => 'smallint'],
                            ['Oid' => 'int', 'Name' => 'int'],
                            ['Oid' => 'bigint', 'Name' => 'bigint'],
                            ['Oid' => 'text', 'Name' => 'text'],
                            ['Oid' => 'longtext', 'Name' => 'longtext'],
                            ['Oid' => 'mediumtext', 'Name' => 'mediumtext'],
                            ['Oid' => 'varchar', 'Name' => 'varchar'],
                        ],
                        'default' => 'varchar',
                    ],
                ]
            ],
            [
                'name' => 'Seperator',
                'type' => 'seperator',
            ],
            $this->actionTable(false),
            [
                'name' => 'Table - Coding',
                'icon' => 'DatabaseIcon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => 'development/table/{Url.APITable}',
                'portalpost' => 'development/table/save/{Url.APITable}',
                'afterRequest' => "apply",
                'form' => [
                    [
                        'fieldToSave' => "APISaveNotBatch",
                        'overrideLabel' => "API FOR SAVING (NOT BATCH)",
                        'type' => "inputtext",
                    ],
                    [
                        'fieldToSave' => "APIDeleteNotBatch",
                        'overrideLabel' => "API FOR DELETE (NOT BATCH)",
                        'type' => "inputtext",
                    ],
                    [
                        'fieldToSave' => "MultiButton",
                        'overrideLabel' => "Coding: Action Top Button (Ex. Add)",
                        'type' => "inputarea",
                    ],
                    [
                        'fieldToSave' => "ActionDropDown",
                        'overrideLabel' => "Coding: Action Drop Down (Table)",
                        'type' => "inputarea",
                    ],
                    [
                        'fieldToSave' => "Presearch",
                        'overrideLabel' => "Coding: Presearch",
                        'type' => "inputarea",
                    ],
                    [
                        'fieldToSave' => "AdditionalTab",
                        'overrideLabel' => "Coding: Additional Tab",
                        'type' => "inputarea",
                    ],
                    [
                        'fieldToSave' => "IsUsingModuleImage",
                        'overrideLabel' => "Use Image",
                        'type' => "checkbox",
                        'default' => false,
                        'column' => '1/4',
                    ],
                    [
                        'fieldToSave' => "IsUsingModuleApproval",
                        'overrideLabel' => "Use Approval",
                        'type' => "checkbox",
                        'default' => false,
                        'column' => '1/4',
                    ],
                    [
                        'fieldToSave' => "IsUsingModuleFile",
                        'overrideLabel' => "Use File",
                        'type' => "checkbox",
                        'default' => false,
                        'column' => '1/4',
                    ],
                    [
                        'fieldToSave' => "IsUsingModuleComment",
                        'overrideLabel' => "Use Comment",
                        'type' => "checkbox",
                        'default' => false,
                        'column' => '1/4',
                    ],
                ]
            ],
            [
                'name' => 'Reorder',
                'icon' => 'DatabaseIcon',
                'type' => 'edit_reorder',
                'portalget' => "development/table/update/reorder?code={Url.APITable}",
                'portalpost' => "development/table/update/reorder?code={Url.APITable}",
            ],
            [
                'name' => 'Seperator',
                'type' => 'seperator',
            ],
            [
                'name' => 'SQL Update',
                'icon' => 'EyeIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/sqlupdate?action=sqlupdate&table={Url.APITable}",
            ],
            [
                'name' => 'SQL Insert',
                'icon' => 'EyeIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/sqlinsert?action=sqlinsert&table={Url.APITable}",
            ],
            [
                'name' => 'PHP Controller',
                'icon' => 'EyeIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/phpcontroller?oid={Url.APITable}",
            ],
            [
                'name' => 'PHP Entity',
                'icon' => 'EyeIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/phpentity?oid={Url.APITable}",
            ],
            [
                'name' => 'JSON',
                'icon' => 'EyeIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/json?oid={Url.APITable}",
            ],
            [
                'name' => 'Vue Transaction',
                'icon' => 'EyeIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/vuetransaction?oid={Url.APITable}",
            ],
            [
                'name' => 'Seperator',
                'type' => 'seperator',
            ],
            [
                'name' => 'Batch - Table',
                'icon' => 'ListIcon',
                'type' => 'edit_tablebatch',
                'portalget' => "development/table/update/table?title=table&code={Url.APITableName}",
                'portalpost' => "development/table/update/table?title=table&code={Url.APITableName}",
            ], 
            [
                'name' => 'Batch - Layout',
                'icon' => 'ListIcon',
                'type' => 'edit_tablebatch',
                'portalget' => "development/table/update/layout?title=layout&code={Url.APITableName}",
                'portalpost' => "development/table/update/layout?title=layout&code={Url.APITableName}",
            ],  
            [
                'name' => 'Batch - Detail',
                'icon' => 'ListIcon',
                'type' => 'edit_tablebatch',
                'portalget' => "development/table/update/detail?title=detail&code={Url.APITableName}",
                'portalpost' => "development/table/update/detail?title=detail&code={Url.APITableName}",
            ],  
            [
                'name' => 'Batch - Combo',
                'icon' => 'ListIcon',
                'type' => 'edit_tablebatch',
                'portalget' => "development/table/update/combo?title=combo&code={Url.APITableName}",
                'portalpost' => "development/table/update/combo?title=combo&code={Url.APITableName}",
            ], 
            [
                'name' => 'Batch - Coding',
                'icon' => 'ListIcon',
                'type' => 'edit_tablebatch',
                'portalget' => "development/table/update/coding?title=coding&code={Url.APITableName}",
                'portalpost' => "development/table/update/coding?title=coding&code={Url.APITableName}",
            ],
        ];
    }

    private function action() {
        return [
            $this->actionForm(false),
            [
                'name' => 'Edit Layout',
                'icon' => 'EditIcon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => 'development/field/{Oid}',
                'portalpost' => 'development/table/savefield/{Oid}',
                'afterRequest' => "apply",
                'config' => 'development/field/field?type=fieldlayout'
            ], 
            [
                'name' => 'Edit Coding',
                'icon' => 'EditIcon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => 'development/field/{Oid}',
                'portalpost' => 'development/table/savefield/{Oid}',
                'afterRequest' => "apply",
                'config' => 'development/field/field?type=fieldcoding'
            ],            
            [
                'name' => 'Delete',
                'icon' => 'TrashIcon',
                'type' => 'confirm',
                'delete' => 'development/field/{Oid}',
            ]
        ];
    }
    public function show($data)
    {
        if ($data == 'undefined') return null;
        $query = "SELECT tbf.*, tbp.Name AS APITableName, tbc.Name AS APITableComboName
            FROM ezb_server.apitablefield tbf
            LEFT OUTER JOIN ezb_server.apitable tbp ON tbp.Oid = tbf.APITable
            LEFT OUTER JOIN ezb_server.apitable tbc ON tbc.Oid = tbf.APITableCombo
            WHERE tbf.Oid='{$data}'";
        $data = $this->dbConnection->select(DB::raw($query))[0];
        $data->Action = $this->action();
        $data = collect($data);
        return $data;
    }

    public function destroy($data)
    {
        $query = "DELETE FROM apitablefield WHERE Oid='{$data}'";
        $data = $this->dbConnection->select(DB::delete($query));
    }
}
