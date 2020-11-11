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

class DevelopmentReportDetailController extends Controller
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
        $fields[] = ['w'=> 200, 'r'=>0, 't'=>'text', 'n'=>'ReportType'];
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
        if (!$request->has('ModuleReport')) return null;
        $criteria = "WHERE d.ModuleReport='".$request->input('ModuleReport')."'";
        if ($request->has('search')){
            $search = $request->input('search');
            $criteria = $criteria." AND (d.Name LIKE '%{$search}%' OR d.Code LIKE '%{$search}%')";
        }
        return $this->subList($criteria, false);
    }
    private function subList ($where = null, $noAction = true) {
        $query = "SELECT d.Oid, d.Code, d.Name, d.ReportType FROM ezb_server.apireport d ".$where.
                " ORDER BY d.Code";
        $data = $this->dbConnection->select(DB::raw($query));
        
        $query = "SELECT Code AS Oid, CONCAT(Code, ' ', Name) AS Name FROM sysmodule WHERE ReportAPI IS NOT NULL ORDER BY Code";
        $combo = $this->dbConnection->select(DB::raw($query));
        if (!$noAction) foreach ($data as $row) {
            $row->DefaultAction = $this->action($combo);
            $row->Action = $this->action($combo);
        }
        return $data;
    }

    public function presearch(Request $request) {
        $query = "SELECT Code AS Oid, CONCAT(Code, ' ', Name) AS Name FROM sysmodule WHERE ReportAPI IS NOT NULL ORDER BY Code";
        $combo = $this->dbConnection->select(DB::raw($query));
        return [
            [
                'fieldToSave'=> 'ModuleReport',
                'overrideLabel'=> 'Token',
                'type'=> 'combobox',
                'column' => '1/3',
                'source'=> $combo,
                'hiddenField'=> 'ModuleReportName',
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
        $query = "SELECT Code AS Oid, CONCAT(Code, ' ', Name) AS Name FROM sysmodule WHERE ReportAPI IS NOT NULL ORDER BY Code";
        $combo = $this->dbConnection->select(DB::raw($query));
        return [
            [
                'fieldToSave' => "ModuleReport",
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
                'fieldToSave' => 'ReportType',
                'type' => 'combobox',
                'column' => '1/2',
                'default' => null,
                'source' => [
                    [
                        'Oid' => 'Parent',
                        'Name' => 'Parent'
                    ],
                    [
                        'Oid' => 'Summary',
                        'Name' => 'Summary'
                    ],
                    [
                        'Oid' => 'Detail',
                        'Name' => 'Detail'
                    ],
                    [
                        'Oid' => 'DetailSummary',
                        'Name' => 'DetailSummary'
                    ],
                ]
            ],
            [
                'fieldToSave' => 'PaperSize',
                'type' => 'combobox',
                'column' => '1/2',
                'default' => null,
                'source' => [
                    [
                        'Oid' => 'Potrait',
                        'Name' => 'Potrait'
                    ],
                    [
                        'Oid' => 'Landscape',
                        'Name' => 'Landscape'
                    ],
                ]
            ],
            [ 
                'fieldToSave' => "FieldsParent",
                'type' => "inputarea"
            ],
            [ 
                'fieldToSave' => "FieldsDetail",
                'type' => "inputarea"
            ],
            [ 
                'fieldToSave' => "Columns",
                'type' => "inputarea"
            ],
            [ 
                'fieldToSave' => "Criteria",
                'type' => "inputarea"
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
                'get' => $isAdd ? null : 'development/reportdetail/{Oid}',
                'portalpost' => 'development/savereportdetail'.($isAdd ? '' : '/{Oid}'),
                'afterRequest' => "apply",
                'config'=>'development/reportdetail/config'
            ],
            [
                'name' => 'Delete',
                'icon' => 'TrashIcon',
                'type' => 'confirm',
                'delete' => 'development/reportdetail/{Oid}',
            ]
        ];
    }
    public function show($data)
    {
        if ($data == 'undefined') return null;
        $query = "SELECT d.*, ModuleReport AS ModuleReportName, PaperSize AS PaperSizeName
            FROM ezb_server.apireport d
            WHERE d.Oid='{$data}'";
        $data = $this->dbConnection->select(DB::raw($query))[0];
        $query = "SELECT Code AS Oid, CONCAT(Code, ' ', Name) AS Name FROM sysmodule WHERE ReportAPI IS NOT NULL ORDER BY Code";
        $combo = $this->dbConnection->select(DB::raw($query));
        $data->Action = $this->action($combo);
        $data = collect($data);
        return $data;
    }

    public function destroy($data)
    {
        $query = "DELETE FROM apireport WHERE Oid='{$data}'";
        $data = $this->dbConnection->select(DB::delete($query));
    }
}
