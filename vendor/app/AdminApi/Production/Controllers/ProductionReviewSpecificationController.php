<?php

namespace App\AdminApi\Production\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Production\Entities\Production;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class ProductionReviewSpecificationController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->module = 'prdproduction';
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
    }

    public function fields()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        // $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Oid'];
        $fields[] = ['w' => 100, 'r' => 0, 't' => 'date', 'n' => 'Date'];
        $fields[] = ['w' => 100, 'r' => 0, 't' => 'text', 'n' => 'Code'];
        $fields[] = ['w' => 100, 'r' => 0, 't' => 'text', 'n' => 'GlassType'];
        $fields[] = ['w' => 100, 'r' => 0, 't' => 'text', 'n' => 'Thickness'];
        $fields[] = ['w' => 100, 'r' => 0, 't' => 'text', 'n' => 'CreatedBy'];
        $fields[] = ['w' => 150, 'r' => 0, 't' => 'text', 'n' => 'BusinessPartner'];
        $fields[] = ['w' => 200, 'r' => 0, 't' => 'text', 'n' => 'Note'];
        return $fields;
    }

    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields());
        // foreach ($fields as &$row) { //combosource
        //     if ($row['headerName']  == 'Company') $row['source'] = comboselect('company');
        // };
        return $fields;
    }

    public function presearch(Request $request)
    {
        return [
            [
                'fieldToSave' => 'DateStart',
                'type' => 'inputdate',
                'column' => '1/5',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->startOfMonth()->format('Y-m-d')
            ],
            [
                'fieldToSave' => 'DateUntil',
                'type' => 'inputdate',
                'column' => '1/5',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->endOfMonth()->format('Y-m-d')
            ],
            [
                'fieldToSave' => 'ItemGlass',
                'type' => 'combobox',
                'overrideLabel' => 'Item Glass',
                'column' => '1/5',
                'default' => null,
                'source' => 'combosource/item',
                'hiddenField' => 'ItemTypeGlassName',
                'onClick' => [
                    'action' => 'request', 
                    'store' => 'combosource/item', 
                    'params' => [
                        'term' => "",
                        'type' => "combo",
                        'itemtypecode' => 'Glass'
                    ],
                ],
            ],
            [
                "type" => "action",
                "column" => "1/5"
            ]
        ];
    }

    public function list(Request $request)
    {
        try {
            // $data = DB::table('prdproduction as data')->select('data.*')
            // ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
            // ->leftJoin('prdorderitemdetail AS poid', 'poid.Oid', '=', 'data.ProductionOrderItemDetail')
            // ->leftJoin('prdorderitem AS poi', 'poi.Oid', '=', 'OrderItemDetail.ProductionOrderItem')
            // ->leftJoin('prdorder AS po', 'po.Oid', '=', 'OrderItem.ProductionOrder')
            // ->leftJoin('prditemglass AS pig'.$i. 'pig'.$i.'.Oid', '=', 'OrderItem.ProductionOrder');
            $criteriaWhere = "";
            if ($request->has('DateStart')) {
                $datefrom = Carbon::parse($request->input('DateStart'));
                $criteriaWhere = $criteriaWhere." AND DATE_FORMAT(po.Date, '%Y-%m-%d') >= '".$datefrom."'";
            }
            if ($request->has('DateUntil')) {
                $dateto = Carbon::parse($request->input('DateUntil'));
                $criteriaWhere = $criteriaWhere." AND DATE_FORMAT(po.Date, '%Y-%m-%d') <= '".$dateto."'";
            }
            if ($request->has('ItemGlass')) {
                $criteriaWhere = $criteriaWhere." AND poi.ItemGlass1 = '".$request->input('ItemGlass')."'";
            }
            $query = "SELECT po.Code AS Code, ig1.Name AS GlassType, DATE_FORMAT(po.Date, '%d %M %Y') AS Date, p.Note AS Note, 
                pt.Name AS Thickness, u.UserName AS CreatedBy, bp.Name AS BusinessPartner
                FROM prdproduction p
                LEFT OUTER JOIN prdorderitemdetail poid ON p.ProductionOrderItemDetail = poid.Oid
                LEFT OUTER JOIN prdorderitem poi ON poid.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN prdorder po ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                LEFT OUTER JOIN prditemglass pig1 ON poi.ItemGlass1 = pig1.Oid
                LEFT OUTER JOIN mstitemgroup ig1 ON ig1.Oid = pig1.Oid
                LEFT OUTER JOIN prdthickness pt ON pig1.ProductionThickness = pt.Oid
                LEFT OUTER JOIN user u ON u.Oid = p.CreatedBy
                LEFT OUTER JOIN mstcurrency cur ON po.Currency = cur.Oid
                WHERE p.GCRecord IS NULL {$criteriaWhere}
                ORDER BY Date ASC
                ";
            $data = DB::select($query);

            foreach ($data as $row) {
                $row->Action = [
                    [
                        'name' => 'Open',
                        'icon' => 'ArrowUpRightIcon',
                        'type' => 'open_view',
                        'portalget' => $this->functionGetUrl($row, "view"),
                        'get' => $this->functionGetUrl($row, "get"),
                    ],
                    [
                        'name' => 'Open in detail',
                        'icon' => 'ArrowUpRightIcon',
                        'type' => 'open_form',
                        'url' => $this->functionGetUrl($row),
                    ]
                ];
            }

            return response()->json(
                $data,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function index(Request $request)
    {
        try {
            $data = DB::table($this->module . ' as data');
            $data = $this->crudController->index($this->module, $data, $request, false);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    private function showSub($Oid)
    {
        try {
            $data = $this->crudController->detail($this->module, $Oid);
            return $data;
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function show(Production $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, true);
            });
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function destroy(Production $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
            errjson($e);
        }
    }
}
