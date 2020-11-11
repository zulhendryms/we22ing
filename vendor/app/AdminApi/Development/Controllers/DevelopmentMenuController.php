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

class DevelopmentMenuController extends Controller
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
        $fields[] = ['w'=> 500, 'r'=>0, 't'=>'text', 'n'=>'Name'];
        $fields[] = ['w'=> 70, 'r'=>0, 't'=>'text', 'n'=>'IsActive'];
        $fields[] = ['w'=> 500, 'r'=>0, 't'=>'text', 'n'=>'Url'];
        return $fields;
    }

    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields(),false,true);
        $fields[0]['cellRenderer'] = 'actionCell';
        $fields[0]['topButton'] = [ $this->action(true)[0] ];
        return $fields;
    }

    public function list(Request $request)
    {
        $criteria = null;
        if ($request->has('search')) {
            $search = $request->input('search');
            $criteria = "AND Name LIKE '%{$search}%' OR Code LIKE '%{$search}%'";
        } elseif ($request->has('SearchKeyword')) {
            $search = $request->input('SearchKeyword');
            $criteria = "AND Name LIKE '%{$search}%' OR Code LIKE '%{$search}%'";
        } else {
            return null;
        }
        return $this->subList($criteria, false);
    }

    private function subList($where = null, $noAction = true)
    {
        $query = "SELECT Oid AS Oid, Code, Name, IsActive, Url FROM sysmodule WHERE GCRecord IS NULL " . $where." ORDER BY Name";
        $data = $this->dbConnection->select(DB::raw($query));
        if (!$noAction) foreach ($data as $row) {
            $row->DefaultAction = $this->action()[0];
            $row->Action = $this->action();
        }
        return $data;
    }

    public function presearch(Request $request)
    {
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
        return  [
            [
                'fieldToSave' => "Code",
                'type' => "inputtext",
                'column' => "1/2",
                'default' => null
            ],
            [
                'fieldToSave' => "Name",
                'type' => "inputtext",
                'column' => "1/2",
                'default' => null
            ],
            [
                'fieldToSave' => "Url",
                'type' => "inputtext",
                'default' => null
            ],
            [
                'fieldToSave' => "IsActive",
                'type' => "checkbox",
                'column' => "1/2",
                'default' => null
            ],
            [
                'fieldToSave' => "Icon",
                'type' => "inputtext",
                'column' => "1/2",
                'default' => null
            ],
            [
                'fieldToSave' => "ModulePOS",
                'type' => "inputtext",
                'default' => null
            ],
            [
                'fieldToSave' => "ModuleAccounting",
                'type' => "inputtext",
                'default' => null
            ],
            [
                'fieldToSave' => "ModuleProductionGlass",
                'type' => "inputtext",
                'default' => null
            ],
            [
                'fieldToSave' => "ModuleTravel",
                'type' => "inputtext",
                'default' => null
            ],
            [
                'fieldToSave' => "ModuleTrucking",
                'type' => "inputtext",
                'default' => null
            ],
        ];
    }

    private function action($isAdd = false) {
             
        return [
            [
                'name' => 'Quick Edit',
                'icon' => 'PlusIcon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => $isAdd ? null : 'development/menu/{Oid}',
                'portalpost' => 'development/savemenu'.($isAdd ? '' : '/{Oid}'),
                'afterRequest' => "apply",
                'config' => 'development/menu/field'
            ],            
            [
                'name' => 'Delete',
                'icon' => 'TrashIcon',
                'type' => 'confirm',
                'portaldelete' => 'development/menu/{Oid}',
                // 'delete' => 'development/menu/{Oid}',
            ]
        ];
    }

    public function show($data)
    {
        if ($data == 'undefined') return null;
        $query = "SELECT sys.*
            FROM sysmodule sys
            WHERE sys.Oid='{$data}'";
        $data = $this->dbConnection->select(DB::raw($query))[0];
        $data->Action = $this->action();
        $data = collect($data);
        return $data;
    }

    public function destroy($data)
    {
        $query = "DELETE FROM sysmodule WHERE Oid='{$data}'";
        $data = $this->dbConnection->select(DB::delete($query));
    }
}
