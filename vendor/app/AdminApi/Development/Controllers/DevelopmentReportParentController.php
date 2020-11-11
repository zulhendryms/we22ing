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

class DevelopmentReportParentController extends Controller
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
        $fields[] = ['w'=> 500, 'r'=>0, 't'=>'text', 'n'=>'Generator'];
        $fields[] = ['w'=> 500, 'r'=>0, 't'=>'text', 'n'=>'TableParent'];
        $fields[] = ['w'=> 500, 'r'=>0, 't'=>'text', 'n'=>'TableDetail'];
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
        $criteria = " WHERE ReportAPI IS NOT NULL ";
        if ($request->has('search')) {
            $search = $request->input('search');
            $criteria = $criteria." AND Name LIKE '%{$search}%' OR Code LIKE '%{$search}%'";
        }
        return $this->subList($criteria, false);
    }

    private function subList($where = null, $noAction = true)
    {
        $query = "SELECT Code AS Oid, Code, Name, IsReportGenerator AS Generator, TableParent, TableDetail FROM ezb_server.sysmodule " . $where;
        $data = $this->dbConnection->select(DB::raw($query));
        if (!$noAction) foreach ($data as $row) {
            $row->DefaultAction = $this->action()[0];
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
    public function field(Request $request){
        return [
            [
                'fieldToSave' => 'Code',
                'column' => "1/2",
                'type' => 'inputtext',
                'default' => null,
                'disabled' => true,
            ],
            [
                'fieldToSave' => 'Name',
                'column' => "1/2",
                'type' => 'inputtext',
                'default' => null,
                'disabled' => true,
            ],
            [
                'fieldToSave' => 'IsReportGenerator',
                'type' => 'checkbox',
                'default' => null
            ],
            [
                'fieldToSave' => 'ReportAPI',
                'type' => 'inputtext',
                'default' => null
            ],
            [
                'fieldToSave' => 'TableParent',
                'column' => "1/3",
                'type' => 'inputtext',
                'default' => null,
            ],
            [
                'fieldToSave' => 'TableDetail',
                'column' => "1/3",
                'type' => 'inputtext',
                'default' => null,
            ],
            [
                'fieldToSave' => 'ReportCriteriaDate',
                'column' => "1/3",
                'type' => 'inputtext',
                'default' => null
            ],
            [
                'fieldToSave' => 'ReportCriteriaFields',
                'type' => 'inputarea',
                'default' => null
            ],
            [
                'fieldToSave' => 'ReportSQLQuery',
                'type' => 'inputarea',
                'default' => null
            ],
            [
                'fieldToSave' => 'ReportSQLCriteria',
                'type' => 'inputarea',
                'default' => null
            ],
        ];
    }
    private function action($isAdd = false)
    {        
        return [
            [
                'name' => 'Quick Edit',
                'icon' => 'PlusIcon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => $isAdd ? null : 'development/reportparent/{Oid}',
                'portalpost' => 'development/savereport'.($isAdd ? '' : '/{Code}'),
                'afterRequest' => "apply",
            ],            
            [
                'name' => 'Delete',
                'icon' => 'TrashIcon',
                'type' => 'confirm',
                'delete' => 'development/reportparent/{Code}',
            ]
        ];
    }

    public function show($data)
    {
        if ($data == 'undefined') return null;
        $query = "SELECT a.*
            FROM sysmodule a
            WHERE a.Code='{$data}'";
        $data = $this->dbConnection->select(DB::raw($query))[0];
        $data->Action = $this->action();
        $data = collect($data);
        return $data;
    }

    public function destroy($data)
    {
        $query = "DELETE FROM sysmodule WHERE Code='{$data}'";
        $data = $this->dbConnection->select(DB::delete($query));
    }

}
