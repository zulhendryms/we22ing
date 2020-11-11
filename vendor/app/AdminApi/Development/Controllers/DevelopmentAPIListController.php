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

class DevelopmentAPIListController extends Controller
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
        $fields[] = ['w'=> 500, 'r'=>0, 't'=>'text', 'n'=>'Name'];
        $fields[] = ['w'=> 150, 'r'=>0, 't'=>'text', 'n'=>'LastExecutedStatus'];
        $fields[] = ['w'=> 150, 'r'=>0, 't'=>'text', 'n'=>'LastExecutedDate'];
        return $fields;
    }

    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields(),false,true);
        $fields[0]['cellRenderer'] = 'actionCell';
        $fields[0]['topButton'] = [ $this->action(null,true)[0] ];
        return $fields;
    }

    public function list(Request $request)
    {
        $criteria = null;
        if (!$request->has('APIToken')) return null;
        $criteria = "WHERE d.APIToken='".$request->input('APIToken')."'";
        if ($request->has('search')){
            $search = $request->input('search');
            $criteria = $criteria." AND (d.Name LIKE '%{$search}%' OR d.Code LIKE '%{$search}%')";
        }
        return $this->subList($criteria, false);
    }
    private function subList ($where = null, $noAction = true) {
        $query = "SELECT d.Oid, d.Code, d.Name, d.LastExecutedStatus, d.LastExecutedDate 
                FROM ezb_server.apitoken p
                LEFT OUTER JOIN ezb_server.apilist d ON d.APIToken = p.Oid "
                .$where."
                ORDER BY d.Code";
        $data = $this->dbConnection->select(DB::raw($query));
        
        $query = "SELECT Oid, CONCAT(Code, ' ', Name) AS Name FROM apitoken ORDER BY Code";
        $combo = $this->dbConnection->select(DB::raw($query));
        if (!$noAction) foreach ($data as $row) {
            $row->DefaultAction = $this->action($combo)[0];
            $row->Action = $this->action($combo);
        }
        return $data;
    }

    public function presearch(Request $request) {
        $query = "SELECT Oid, CONCAT(Code, ' ', Name) AS Name FROM apitoken ORDER BY Code";
        $combo = $this->dbConnection->select(DB::raw($query));
        return [
            [
                'fieldToSave'=> 'APIToken',
                'overrideLabel'=> 'Token',
                'type'=> 'combobox',
                'column' => '1/3',
                'source'=> $combo,
                'hiddenField'=> 'APITokenName',
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

    public function field(Request $request) {
        return [
            [
                'fieldToSave' => "APIToken",
                'overrideLabel' => "Choose Token Group",
                'type' => "combobox",
                'store' => "",
                'source' => $combo
            ],
            [
                'fieldToSave' => "Code",
                'type' => "inputtext"
            ],
            [
                'fieldToSave' => "Name",
                'type' => "inputtext"
            ],
            [
                'fieldToSave' => "Method",
                'type' => "combobox",
                'store' => "",
                'source' => [
                    ['Oid' => 'get', 'Name' => 'GET'],
                    ['Oid' => 'post', 'Name' => 'POST'],
                    ['Oid' => 'put', 'Name' => 'PUT'],
                    ['Oid' => 'delete', 'Name' => 'DELETE'],
                ]
            ],
            [
                'fieldToSave' => "Url",
                'type' => "inputtext"
            ],
            [
                'fieldToSave' => "Param",
                'type' => "inputarea"
            ],
            [ 
                'fieldToSave' => "IsActive",
                'type' => "checkbox"
            ],
            [ 
                'fieldToSave' => "IsCRUD",
                'type' => "checkbox"
            ],
            [ 
                'fieldToSave' => "IsPresearch",
                'type' => "checkbox"
            ],
            [ 
                'fieldToSave' => "IsDashboard",
                'type' => "checkbox"
            ],
        ];
    }

    private function action($combo, $isAdd = false) {
        return [
            [
                'name' => 'Quick Edit',
                'icon' => 'PlusIcon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => $isAdd ? null : 'development/apilist/{Oid}',
                'portalpost' => 'development/saveapilist'.($isAdd ? '' : '/{Oid}'),
                'afterRequest' => "apply",
                'config' => 'development/apilist/field'
            ],
            [
                'name' => 'Delete',
                'icon' => 'TrashIcon',
                'type' => 'confirm',
                'delete' => 'development/apilist/{Oid}',
            ]
        ];
    }
    public function show($data)
    {
        if ($data == 'undefined') return null;
        $query = "SELECT d.*, p.Name AS APITokenName
            FROM ezb_server.apilist d
            LEFT OUTER JOIN ezb_server.apitoken p ON p.Oid = d.APIToken
            WHERE d.Oid='{$data}'";
        $data = $this->dbConnection->select(DB::raw($query))[0];
        $query = "SELECT Oid, CONCAT(Code, ' ', Name) AS Name FROM apitoken ORDER BY Code";
        $combo = $this->dbConnection->select(DB::raw($query));
        $data->Action = $this->action($combo);
        $data = collect($data);
        return $data;
    }

    public function destroy($data)
    {
        $query = "DELETE FROM apilist WHERE Oid='{$data}'";
        $data = $this->dbConnection->select(DB::delete($query));
    }
}
