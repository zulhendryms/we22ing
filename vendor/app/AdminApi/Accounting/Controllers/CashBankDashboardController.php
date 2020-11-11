<?php

namespace App\AdminApi\Accounting\Controllers;

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


class CashBankDashboardController extends Controller
{
    protected $roleService;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
    }

    public function cashbankFields()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w' => 0, 'r' => 0, 't' => 'text', 'n' => 'Oid', 'fs' => 'data.Oid'];
        // $fields[] = ['w' => 120, 'r' => 0, 't' => 'text', 'n' => 'Type', 'fs' => 'JournalType.Code'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Code', 'fs' => 'data.Code'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'date', 'n' => 'Date', 'fs' => 'data.Date'];
        $fields[] = ['w' => 200, 'r' => 0, 't' => 'text', 'n' => 'Description'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'double', 'n' => 'DebetBase'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'double', 'n' => 'CreditBase'];
        $fields[] = ['w' => 0, 'h' => 1, 'n' => 'Balance'];
        // $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'BusinessPartner', 'fs' => 'BusinessPartner.Name'];
        // $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Warehouse', 'fs' => 'Warehouse.Code'];
        // $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Project', 'fs' => 'Project.Code'];
        // $fields[] = ['w' => 0, 'h' => 1, 't' => 'text', 'n' => 'CashBank', 'fs' => 'Cashbank.Code'];
        
        return $fields;
    }

    public function cashbankConfig(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->cashbankFields(), false, true);
        $fields[0]['cellRenderer'] = 'actionCell';
        return $fields;
    }

    public function cashbankList(Request $request)
    {
        try{
            $criteriaWhere = "";
            if (!$request->has('Account')) return null;
            if (!$request->has('Company')) return null;
            if ($request->has('Account')) $criteriaWhere = $criteriaWhere." AND Account.Oid = '".$request->input('Account')."'";
            if ($request->has('Company')) $criteriaWhere = $criteriaWhere." AND Company.Oid = '".$request->input('Company')."'";
            if ($request->has('DateStart')) $criteriaWhere = $criteriaWhere." AND data.Date >= '".$request->input('DateStart')."'";
            if ($request->has('DateUntil')) $criteriaWhere = $criteriaWhere." AND data.Date <= '".$request->input('DateUntil')."'";

            $query = "SELECT 
                CASE 
                    WHEN JournalType.Code = 'POS' THEN data.PointOfSale 
                    WHEN JournalType.Code = 'CASH' THEN data.CashBank
                    WHEN JournalType.Code = 'GL' THEN data.GeneralJournal
                    WHEN JournalType.Code = 'PDO' THEN data.PurchaseDelivery
                    WHEN JournalType.Code = 'PINV' THEN data.PurchaseInvoice
                    WHEN JournalType.Code = 'SDO' THEN data.SalesDelivery
                    WHEN JournalType.Code = 'SINV' THEN data.SalesInvoice
                END AS Oid,
                JournalType.Code Type, data.Code, data.Date, data.Note Description, SUM(IFNULL(data.DebetAmount,0)) AS DebetBase, SUM(IFNULL(data.CreditAmount,0)) AS CreditBase, 
                BusinessPartner.Name BusinessPartner, Warehouse.Code Warehouse, Project.Code Project, 0 AS Balance
                FROM accjournal data
                LEFT OUTER JOIN accaccount Account ON data.Account = Account.Oid
                LEFT OUTER JOIN sysjournaltype JournalType ON data.JournalType = JournalType.Oid
                LEFT OUTER JOIN mstbusinesspartner BusinessPartner ON data.BusinessPartner = BusinessPartner.Oid
                LEFT OUTER JOIN mstwarehouse Warehouse ON data.Warehouse = Warehouse.Oid
                LEFT OUTER JOIN mstproject Project ON data.Project = Project.Oid
                LEFT OUTER JOIN company Company On data.Company = Company.Oid
                WHERE data.GCRecord IS NULL {$criteriaWhere}
                GROUP BY data.Date, data.Code, data.PointOfSale, data.CashBank, data.GeneralJournal, data.PurchaseDelivery, data.PurchaseInvoice, data.SalesDelivery, data.SalesInvoice,
                JournalType.Code, data.Note, BusinessPartner.Name, Warehouse.Code, Project.Code";
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

            $totalBalance = 0;
            foreach($data as $row) {
                $totalBalance = $totalBalance + $row->DebetBase - $row->CreditBase;
                $row->Balance = $totalBalance;
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

    private function functionGetUrl($row, $type = null)
    {
        if (!isset($row->Oid)) return null;
        if ($row->Type == 'PurchaseOrder') {
            $tmp = PurchaseOrder::where('Oid', $row->Oid)->first();
            if ($tmp) {
                if ($type == null) return "purchaseorder/form?item=" . $row->Oid . "&type=" . $tmp->Type . "&returnUrl=accounthistory%3FCompany%3D{Url.Company}";
                elseif ($type == 'get') return "purchaseorder/" . $row->Oid;
                elseif ($type == 'view') return "development/table/vueview?code=PurchaseOrder";
            }
        } elseif ($row->Type == 'CASH') {
            $tmp = CashBank::where('Oid', $row->Oid)->first();
            if ($tmp) {
                if ($type == null) return "cashbank/form?item=" . $row->Oid . "&type=" . $tmp->Type . "&returnUrl=accounthistory%3FCompany%3D{Url.Company}";
                elseif ($type == 'get') return "cashbank/" . $row->Oid;
                elseif ($type == 'view') return "development/table/vueview?code=CashBank";
            }
        }
    }

    public function cashbankPresearch(Request $request)
    {
        return [
            [
                'fieldToSave' => 'Company',
                "hiddenField" => "CompanyName",
                'type' => 'combobox',
                'column' => '1/5',
                'validationParams' => 'required',
                'source' => 'company',
                'onClick' => [
                    'action' => 'request',
                    'store' => 'combosource/company',
                    'params' => null
                ]
            ],
            [
                'fieldToSave' => 'Account',
                "hiddenField" => "AccountName",
                'type' => 'autocomplete',
                'column' => '1/5',
                'validationParams' => 'required',
                'default' => null,
                'source' => [],
                'store' => 'autocomplete/account',
                'params' => [
                    'type' => 'combo',
                    'form' => 'cashbank',
                    'term' => ''
                ]
            ],
            [
                'fieldToSave' => 'DateFrom',
                'type' => 'inputdate',
                'column' => '1/5',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->startOfMonth()->format('Y-m-d')
            ],
            [
                'fieldToSave' => 'DateTo',
                'type' => 'inputdate',
                'column' => '1/5',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->endOfMonth()->format('Y-m-d')
            ],
            [
                'type' => 'action',
                'column' => '1/6'
            ]
        ];
    }
}
