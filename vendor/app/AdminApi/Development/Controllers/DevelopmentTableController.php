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

class DevelopmentTableController extends Controller
{
    //table
    //field
    //batchedit: ok
    //viewsyntax: ok
    //reorder: ok
    //dashboard
    //report
    //menu    

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
        $fields[] = ['w' => 200, 'r' => 0, 't' => 'text', 'n' => 'Code'];
        $fields[] = ['w' => 500, 'r' => 0, 't' => 'text', 'n' => 'Name'];
        $fields[] = ['w' => 150, 'r' => 0, 't' => 'text', 'n' => 'FormType'];
        $fields[] = ['w' => 120, 'r' => 0, 't' => 'text', 'n' => 'IsActive'];
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
        if (!$request->has('SearchKeyword')) return null;
        // $criteria = " AND APITableGroup='".$request->input('APITableGroup')."' ";
        if ($request->has('search')) {
            $search = $request->input('search');
            $criteria = $criteria." AND Name LIKE '%{$search}%' OR Code LIKE '%{$search}%'";        
        } elseif ($request->has('SearchKeyword')) {
            $search = $request->input('SearchKeyword');
            $criteria = "AND Name LIKE '%{$search}%' OR Code LIKE '%{$search}%'";
        }
        return $this->subList($criteria, true);
    }

    private function subList($where = null, $withAction = true)
    {
        $query = "SELECT Oid, Code, CONCAT(Code, ' - ', Name) AS Name 
            FROM apitable 
            WHERE LEFT(Code,3) NOT IN ('api','imp','not','shi','tbo','tab','use','wir','att','glo','b_c','b_l','gro','fil','job','mig','ite','mod','oau','ana','aud','das','dev',
            'fai','hca','mya','myr','per','rep','ser','ses','tok','xpo','xpw') ";
        $query = $query.$where." ORDER BY Code";
        $data = $this->dbConnection->select(DB::raw($query));

        if ($withAction) {
            foreach ($data as $row) {
                $row->DefaultAction = $this->action()[0];
                $row->Action = $this->action();
            }
        }
        return $data;
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

    public function presearch(Request $request) {
        return [
            [
                'fieldToSave' => "SearchKeyword",
                'hideLabel' => true,
                'type' => "inputtext",
                'column' => "1/2"
            ],
            [
                'type' => 'action',
                'column' => '1/5'
            ]
        ];
    }

    public function index(Request $request)
    {
        return null;
    }
    
    public function field(Request $request) {
        if ($request->input('form')) {            
            return [
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
                    'column' => '1/2'
                ],
                [
                    'fieldToSave' => "IsComboAutoComplete",
                    'overrideLabel' => "Is Combo AutoComplete?",
                    'type' => "checkbox",
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
                    'column' => '1/3'
                ],
                [
                    'fieldToSave' => "GlobalIsRequired",
                    'overrideLabel' => "Global: Required",
                    'type' => "checkbox",
                    'column' => '1/3'
                ],
            ];
        } elseif ($request->input('coding')) {            
            return [
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
                    'fieldToSave' => "ActionDropDownRow",
                    'overrideLabel' => "Coding: Action Drop Down (Table)",
                    'type' => "inputarea",
                ],
                [
                    'fieldToSave' => "Presearch",
                    'overrideLabel' => "Coding: Presearch",
                    'type' => "inputarea",
                ],
                [
                    'fieldToSave' => "CustomTabHideWhen",
                    'overrideLabel' => "Coding: Tab HideWhen",
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
                    'column' => '1/4',
                ],
                [
                    'fieldToSave' => "IsUsingModuleApproval",
                    'overrideLabel' => "Use Approval",
                    'type' => "checkbox",
                    'column' => '1/4',
                ],
                [
                    'fieldToSave' => "IsUsingModuleFile",
                    'overrideLabel' => "Use File",
                    'type' => "checkbox",
                    'column' => '1/4',
                ],
                [
                    'fieldToSave' => "IsUsingModuleComment",
                    'overrideLabel' => "Use Comment",
                    'type' => "checkbox",
                    'column' => '1/4',
                ],
            ];
        }
    }

    private function action($isAdd = false)
    {
        return [
            [
                'name' => ($isAdd ? 'Create' : 'Edit').' Table',
                'icon' => 'Edit2Icon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => $isAdd ? null : 'development/table/{Oid}',
                'portalpost' => 'development/table/save' . ($isAdd ? '' : '/{Oid}'),
                'afterRequest' => "apply",
                'config' => 'development/table/field?type=form',
            ],                
            [
                'name' => 'Edit Coding',
                'icon' => 'Edit2Icon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => $isAdd ? null : 'development/table/{Oid}',
                'portalpost' => 'development/table/save'.($isAdd ? '' : '/{Oid}'),
                'afterRequest' => "apply",
                'config' => 'development/table/field?type=form',
            ],
            [
                'name' => 'View Field List',
                'icon' => 'BookOpenIcon',
                'type' => 'open_form',
                'url' => "development/field?APITable={Oid}&APITableName={Name}",
            ],
            [
                'name' => 'Edit Reorder',
                'icon' => 'ListIcon',
                'type' => 'edit_reorder',
                'portalget' => "development/table/update/reorder?code={Code}",
                'portalpost' => "development/table/update/reorder?code={Code}",
            ],
            [
                'name' => 'Syntax SQL Update',
                'icon' => 'ListIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/sqlupdate?action=sqlupdate&table={Oid}",
            ],
            [
                'name' => 'Syntax SQL Insert',
                'icon' => 'ListIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/sqlinsert?action=sqlinsert&table={Oid}",
            ],
            [
                'name' => 'Syntax PHP Controller',
                'icon' => 'ListIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/phpcontroller?oid={Oid}",
            ],
            [
                'name' => 'Syntax PHP Entity',
                'icon' => 'ListIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/phpentity?oid={Oid}",
            ],
            [
                'name' => 'Syntax JSON',
                'icon' => 'ListIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/json?oid={Oid}",
            ],
            [
                'name' => 'Syntax Vue Transaction',
                'icon' => 'ListIcon',
                'type' => 'preview_code',
                'portalget' => "development/table/vuetransaction?oid={Oid}",
            ],
            [
                'name' => 'Batch Edit Table',
                'icon' => 'ListIcon',
                'type' => 'edit_tablebatch',
                'portalget' => "development/table/update/table?title=table&code={Code}",
                'portalpost' => "development/table/update/table?title=table&code={Code}",
            ], 
            [
                'name' => 'Batch Edit Layout',
                'icon' => 'ListIcon',
                'type' => 'edit_tablebatch',
                'portalget' => "development/table/update/layout?title=layout&code={Code}",
                'portalpost' => "development/table/update/layout?title=layout&code={Code}",
            ],  
            [
                'name' => 'Batch Edit Detail',
                'icon' => 'ListIcon',
                'type' => 'edit_tablebatch',
                'portalget' => "development/table/update/detail?title=detail&code={Code}",
                'portalpost' => "development/table/update/detail?title=detail&code={Code}",
            ],  
            [
                'name' => 'Batch Edit Combo',
                'icon' => 'ListIcon',
                'type' => 'edit_tablebatch',
                'portalget' => "development/table/update/combo?title=combo&code={Code}",
                'portalpost' => "development/table/update/combo?title=combo&code={Code}",
            ], 
            [
                'name' => 'Batch Edit Coding',
                'icon' => 'ListIcon',
                'type' => 'edit_tablebatch',
                'portalget' => "development/table/update/coding?title=coding&code={Code}",
                'portalpost' => "development/table/update/coding?title=coding&code={Code}",
            ],
        ];
    }    
    
    public function autocomplete(Request $request)
    {
        $term = $request->term;
        $criteria = "AND Name LIKE '%{$term}%' OR Code LIKE '%{$term}%'";
        $query = "SELECT Oid, Code, Code AS Name 
            FROM apitable 
            WHERE LEFT(Code,3) NOT IN ('api','imp','not','shi','tbo','tab','use','wir','att','glo','b_c','b_l','gro','fil','job','mig','ite','mod','oau','ana','aud','das','dev',
            'fai','hca','mya','myr','per','rep','ser','ses','tok','xpo','xpw') ";
        $data = $this->dbConnection->select(DB::raw($query . $criteria . " ORDER BY Code"));
        return $data;
    }

    public function show($data)
    {
        if ($data == 'undefined') return null;
        $query = "SELECT a.*, a.APITableParentRelationshipName, CONCAT(tbp.Code, ' - ', tbp.Name) AS APITableParentDisplayName
            FROM apitable a
            LEFT OUTER JOIN apitable tbp ON tbp.Oid = a.APITableParent
            WHERE a.Oid='{$data}'";
        $data = $this->dbConnection->select(DB::raw($query))[0];
        $data->Action = $this->action();
        $data = collect($data);
        return $data;
    }

    public function destroy($data)
    {
        $query = "DELETE FROM apitable WHERE Oid='{$data}'";
        $data = $this->dbConnection->select(DB::delete($query));
    }
}
