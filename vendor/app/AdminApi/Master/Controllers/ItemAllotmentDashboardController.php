<?php

namespace App\AdminApi\Master\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Company;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Services\AutoNumberService;
use Carbon\Carbon;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

use App\Core\Accounting\Entities\CashBank;
use App\Core\Trading\Entities\PurchaseOrder;
use App\Core\Master\Entities\ItemAllotment;
use App\Core\Master\Entities\Item;

class ItemAllotmentDashboardController extends Controller
{
    protected $roleService;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
    }

    public function fields()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w' => 0, 'h' => 1, 'n' => 'Oid'];
        $fields[] = ['w' => 200, 'r' => 0, 't' => 'text', 'n' => 'BusinessPartner'];
        $fields[] = ['w' => 275, 'r' => 0, 't' => 'text', 'n' => 'Item'];
        $fields[] = ['w' => 70, 'r' => 0, 't' => 'double', 'n' => 'Day1'];
        $fields[] = ['w' => 70, 'r' => 0, 't' => 'double', 'n' => 'Day2'];
        $fields[] = ['w' => 70, 'r' => 0, 't' => 'double', 'n' => 'Day3'];
        $fields[] = ['w' => 70, 'r' => 0, 't' => 'double', 'n' => 'Day4'];
        $fields[] = ['w' => 70, 'r' => 0, 't' => 'double', 'n' => 'Day5'];
        $fields[] = ['w' => 70, 'r' => 0, 't' => 'double', 'n' => 'Day6'];
        $fields[] = ['w' => 70, 'r' => 0, 't' => 'double', 'n' => 'Day7'];
        $fields[] = ['w' => 70, 'r' => 0, 't' => 'double', 'n' => 'Day8'];
        $fields[] = ['w' => 70, 'r' => 0, 't' => 'double', 'n' => 'Day9'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day10'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day11'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day12'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day13'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day14'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day15'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day16'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day17'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day18'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day19'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day20'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day21'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day22'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day23'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day24'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day25'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day26'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day27'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day28'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day29'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day30'];
        $fields[] = ['w' => 75, 'r' => 0, 't' => 'double', 'n' => 'Day31'];
        return $fields;
    }
    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields(), false, true);
        $fields[0]['cellRenderer'] = 'actionCell';
        return $fields;
    }

    public function list(Request $request)
    {
        try {
            $criteria = null;
            $period = $request->input('Period');
            if ($request->has('Company')) $criteria = $criteria . " AND i.Company = '{$request->input('Company')}'";
            if ($request->has('BusinessPartner')) $criteria = $criteria . " AND i.PurchaseBusinessPartner = '{$request->input('BusinessPartner')}'";
            if ($request->has('FerryRoute')) $criteria = $criteria . " AND i.FerryRoute = '{$request->input('FerryRoute')}'";

            $query = "SELECT i.Oid AS Oid, bp.Name AS BusinessPartner, i.Name AS Item,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',1),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day1,0)) END Day1,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',2),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day2,0)) END Day2,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',3),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day3,0)) END Day3,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',4),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day4,0)) END Day4,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',5),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day5,0)) END Day5,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',6),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day6,0)) END Day6,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',7),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day7,0)) END Day7,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',8),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day8,0)) END Day8,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',9),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day9,0)) END Day9,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',10),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day10,0)) END Day10,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',11),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day11,0)) END Day11,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',12),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day12,0)) END Day12,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',13),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day13,0)) END Day13,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',14),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day14,0)) END Day14,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',15),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day15,0)) END Day15,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',16),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day16,0)) END Day16,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',17),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day17,0)) END Day17,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',18),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day18,0)) END Day18,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',19),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day19,0)) END Day19,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',20),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day20,0)) END Day20,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',21),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day21,0)) END Day21,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',22),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day22,0)) END Day22,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',23),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day23,0)) END Day23,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',24),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day24,0)) END Day24,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',25),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day25,0)) END Day25,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',26),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day26,0)) END Day26,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',27),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day27,0)) END Day27,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',28),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day28,0)) END Day28,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',29),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day29,0)) END Day29,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',30),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day30,0)) END Day30,
                CASE WHEN STR_TO_DATE(CONCAT(LEFT('{$period}',4),'-',RIGHT('{$period}',2),'-',31),'%Y-%m-%d') < NOW() THEN 0 ELSE IFNULL(i.QuantityAllotment,0) + SUM(IFNULL(a.Day31,0)) END Day31
                FROM mstitem i 
                LEFT OUTER JOIN mstitemallotment a ON i.Oid = a.Item AND a.Period = '{$period}'
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = i.PurchaseBusinessPartner
                LEFT OUTER JOIN sysitemtype s ON i.ItemType = s.Oid
                LEFT OUTER JOIN ferroute r ON i.FerryRoute = r.Oid
                WHERE i.IsActive = true AND s.Code = 'TravelFerry' {$criteria}
                GROUP BY i.Oid, bp.Name, i.Name
                ORDER BY i.FerryTime
                ";
            $data = DB::select($query);
            foreach ($data as $row) {
                $row->Action = $this->action();
            }
            return response()->json(
                $data,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function presearch(Request $request)
    {
        return [
            [
                'fieldToSave' => 'Company',
                'hiddenField' => 'CompanyName',
                'type' => 'combobox',
                'column' => '1/5',
                'validationParams' => 'required',
                'source' => 'company',
                'onClick' => ['action' => 'request', 'store' => 'combosource/company', 'params' => null]
            ], [
                'fieldToSave' => 'Period',
                'type' => 'inputtext',
                'column' => '1/5',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->startOfMonth()->format('Ym')
            ], [
                'fieldToSave' => 'BusinessPartner',
                'validationParams' => 'required',
                'type' => 'autocomplete',
                'column' => '1/5',
                'source' => [],
                'store' => 'autocomplete/businesspartner',
                'hiddenField'=> 'BusinessPartnerName',
                'default' => [
                    'localCompany',
                    'BusinessPartner'
                ],
            ], [
                'fieldToSave' => 'FerryRoute',
                'type' => 'combobox',
                'overrideLabel' => 'FerryRoute',
                'column' => '1/5',
                'default' => null,
                'source' => 'data/ferryroute',
                'hiddenField'=> 'FerryRouteName',
                'onClick' => ['action' => 'request', 'store' => 'data/ferryroute', 'params' => null],
            ], [
                'type' => 'action',
                'column' => '1/5'
            ]
        ];
    }

    private function functionGetUrl($row, $type = null)
    {
        if (!isset($row->Oid)) {
            return null;
        }
        if ($row->Type == 'PurchaseOrder') {
            $tmp = PurchaseOrder::where('Oid', $row->Oid)->first();
            if ($tmp) {
                if ($type == null) {
                    return "purchaseorder/form?item=" . $row->Oid . "&type=" . $tmp->Type . "&returnUrl=accounthistory%3FCompany%3D{Url.Company}";
                } elseif ($type == 'get') {
                    return "purchaseorder/" . $row->Oid;
                } elseif ($type == 'view') {
                    return "development/table/vueview?code=PurchaseOrder";
                }
            }
        } elseif ($row->Type == 'CASH') {
            $tmp = CashBank::where('Oid', $row->Oid)->first();
            if ($tmp) {
                if ($type == null) {
                    return "cashbank/form?item=" . $row->Oid . "&type=" . $tmp->Type . "&returnUrl=accounthistory%3FCompany%3D{Url.Company}";
                } elseif ($type == 'get') {
                    return "cashbank/" . $row->Oid;
                } elseif ($type == 'view') {
                    return "development/table/vueview?code=CashBank";
                }
            }
        }
    }

    private function action()
    {
        $fields = [
            [
                'fieldToSave' => 'Period',
                'type' => 'inputtext',
                'default' => '{Parent.Period}',
                // 'disabled' => true,
            ],
            // [
            //     'fieldToSave' => 'Item',
            //     'type' => 'inputtext',
            //     'default' => '{Oid}',
            //     // 'disabled' => true,
            //     'validationParams' => 'required',
            // ],
            [
                'fieldToSave' => 'DateStart',
                'type' => 'inputtext',
                'validationParams' => 'required|money',
                'default' => 1
            ],
            [
                'fieldToSave' => 'DateUntil',
                'type' => 'inputtext',
                'validationParams' => 'required|money',
                'default' => 31
            ],
            [
                'fieldToSave' => 'Quantity',
                'type' => 'inputtext',
                'validationParams' => 'required|money',
            ]
        ];
        $data = [
            [
            'name' => 'Add ',
            'icon' => 'PlusIcon',
            'type' => 'global_form',
            'showModal' => false,
            'post' => 'itemallotmentdashboard/create/{Oid}',
            'afterRequest' => "init",
            'form' => $fields
            ],
        ];
        return $data;
    }

    public function create(Request $request, $Oid=null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data,$Oid) {
                $user = Auth::user();
                $Item = Item::where('Oid',$Oid)->first();
                $data = new ItemAllotment();
                $data->Company = $user->Company;
                $data->Item =  $Item->Oid;
                $data->Period = $request->Period;
                for ($i = (float)$request->DateStart; $i <= (float)$request->DateUntil; $i++) {
                    $data->{'Day' . $i} =(float)$request->Quantity;
                }
                $data->save();
            });
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            errjson($e);
        }
    }
}
