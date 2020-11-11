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
use App\Core\Accounting\Entities\AccountGroup;
use App\Core\Accounting\Entities\AccountSection;

class AccountDashboardController extends Controller
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
        $fields[] = ['w' => 0, 'r' => 0, 't' => 'text', 'n' => 'Oid', 'fs' => 'a.Oid'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Code', 'fs' => 'a.Code'];
        $fields[] = ['w' => 400, 'r' => 0, 't' => 'text', 'n' => 'AccountName', 'fs' => 'AccountName'];
        // $fields[] = ['w' => 200, 'r' => 0, 't' => 'text', 'n' => 'AccountGroupName', 'fs' => 'AccountGroupName'];
        $fields[] = ['w' => 200, 'r' => 0, 't' => 'double', 'n' => 'Amount', 'fs' => 'Amount'];
        $fields[] = ['w' => 200, 'r' => 0, 't' => 'double', 'n' => 'Company', 'fs' => 'Company'];
        $fields[] = ['w' => 200, 'r' => 0, 't' => 'double', 'n' => 'CompanyName', 'fs' => 'CompanyName'];
        return $fields;
    }

    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields(), false, true);
        $fields[0]['cellRenderer'] = 'actionCell';
        $fields[0]['topButton'] = [$this->popup(true)];
        // $fields[0]['topButton'] = [
        //     [
        //     'name' => 'New Account',
        //     'icon' => 'DocumentIcon',
        //     'type' => 'open_form',
        //     'url' => "account/form"
        //     ]
        // ];
        $fields[5]['width'] = 0;
        $fields[6]['width'] = 0;
        $fields[5]['hide'] = true;
        $fields[6]['hide'] = true;
        return $fields;
    }

    public function field (Request $request) {
        return $this->crudController->jsonFieldPopup('Account',[
            'Company','Code','Name','DefaultDescription','AccountGroup','AccountType','Bank','Currency','IsActive'
        ]);
    }
    private function popup($isCreate = true)
    {
        $data = [
            'name' => 'Quick ' . ($isCreate ? 'Add' : 'Edit'),
            'icon' => 'PlusIcon',
            'type' => 'global_form',
            'showModal' => false,
            'post' => 'data/account',
            'afterRequest' => "init",
            'config' => 'account/dashboard/field'
        ];

        if ($isCreate) {
            $data['post'] = 'data/account';
        } else {
            $data['get'] = 'data/account/{Oid}';
            $data['post'] = 'data/account/{Oid}';
        }
        return $data;
    }

    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$request->has('Company')) {
                return null;
            }
            $company = Company::findOrFail($request->has('Company') ? $request->input('Company') : $user->Company);
            $criteriaCompany = " AND (aco.Oid = '{$company->Oid}' 
                OR aco.Oid = '{$company->CompanySource}'
                OR aco.Oid = '{$company->CompanySourceObj->CompanySource}') ";

            $query = "SELECT 
                        ag.Oid AS AccountGroup,ag.Name AS AccountGroupCode, 
                        a.Oid AS Oid, a.Code AS Code, CONCAT('--',a.Name) AS AccountName, 
                        FORMAT(SUM(IFNULL(j.DebetAmount,0)- IFNULL(j.CreditAmount,0)),2) AS Amount, 
                        uco.Oid AS Company, uco.Code AS CompanyName
                    FROM accaccount a
                    LEFT OUTER JOIN accaccountgroup ag ON a.AccountGroup = ag.Oid
                    LEFT OUTER JOIN sysaccounttype act ON a.AccountType = act.Oid
                    LEFT OUTER JOIN accjournal j ON a.Oid = j.Account AND j.Company = '{$company->Oid}'
                    LEFT OUTER JOIN mstbank b ON b.Oid = a.Bank
                    LEFT OUTER JOIN company co ON co.Oid = j.Company
                    LEFT OUTER JOIN company aco ON aco.Oid = a.Company
                    LEFT OUTER JOIN company acosc ON acosc.Oid = co.CompanySource
                    LEFT OUTER JOIN company uco ON uco.Oid = '{$company->Oid}'
                    WHERE j.GCRecord IS NULL 
                    AND a.GCRecord IS NULL 
                    {$criteriaCompany}
                    GROUP BY ag.Code, a.Code, a.Name, a.Oid, a.Company, co.Name, ag.Oid";
            
            if ($request->input('Type') == 'All') $query = $query;
            elseif ($request->input('Type') == 'Only') $query = $query." HAVING SUM(IFNULL(j.DebetAmount,0)- IFNULL(j.CreditAmount,0)) > 0";

            $data = DB::select($query);
            $sections = DB::select("SELECT a.*, c.Code AS CompanyCode FROM accaccountsection a LEFT OUTER JOIN company c ON c.Oid = a.Company ORDER BY a.Code");
            $groups = DB::select("SELECT a.*, c.Code AS CompanyCode FROM accaccountgroup a LEFT OUTER JOIN company c ON c.Oid = a.Company ORDER BY a.Code");
            $result = [];

            $edit = $this->popup(false);
            // $edit = [
            //     'name' => 'Edit Account',
            //     'icon' => 'DocumentIcon',
            //     'type' => 'open_form',
            //     'url' => "account/form?item=".$a->Oid."&returnUrl=accountdashboard%3FCompany%3D{Url.Company}"
            // ];
            $open = [
                'name' => 'Open in detail',
                'icon' => 'ArrowUpRightIcon',
                'type' => 'open_form',
                'url' => 'accounthistory?Company={Company}&CompanyName={CompanyName}&Account={Oid}&AccountName={AccountName}&DateStart=' . Carbon::parse(now())->startOfMonth()->format('Y-m-d') . '&DateUnti=' . Carbon::parse(now())->endOfMonth()->format('Y-m-d'),
            ];
            foreach($sections as $s) {
                $s = (object) $s;
                // logger($s->Oid.' '.$s->Code);
                $accountgroups = [];
                $amountgroup = 0;
                foreach ($groups as $g) {
                    $g = (object) $g;
                    if ($g->AccountSection == $s->Oid) {
                        // logger($g->Oid.' '.$g->Code);
                        $accounts = [];
                        $amount = 0;
                        foreach ($data as $a) {
                            if ($a->AccountGroup == $g->Oid) {
                                // logger($a->Oid.' '.$a->Code);
                                $accounts[] = [
                                    "Oid" => $a->Oid,
                                    "Code" => $a->Code,
                                    "AccountName" => $a->AccountName,
                                    "AccountGroupName" => $a->AccountGroupCode,
                                    "Amount" => $a->Amount,
                                    "DefaultAction" => $open,
                                    "Action" => [
                                        $open,
                                        $edit,
                                        [
                                            'name' => 'Delete',
                                            'icon' => 'TrashIcon',
                                            'type' => 'confirm',
                                            'delete' => 'data/account/{Oid}',
                                        ]
                                    ],
                                    "Company" => $a->Company,
                                    "CompanyName" => $a->CompanyName,
                                    "AccountGroup" => $a->AccountGroup,
                                    "Color" => 'FFFFFF'
                                ];
                                $amount = $amount + ((float)str_replace(',', '', $a->Amount) ?: 0);
                            }
                        }
                        $accountgroups[] = [
                            "Oid" => null,
                            "Code" => $g->Code,
                            "AccountName" => $g->Name,
                            "AccountGroupName" => null,
                            "Amount" => number_format($amount, 2),
                            "Company" => $g->Company,
                            "CompanyName" => $g->CompanyCode,
                            "AccountGroup" => null,
                            // "FontWeight" => '500',
                            "Color" => 'fbfcbd'
                        ];
                        $amountgroup = $amountgroup + $amount;
                        foreach ($accounts as $a) $accountgroups[] =$a;
                    }
                }
                $result[] = [
                    "Oid" => null,
                    "Code" => $s->Code,
                    "AccountName" => $s->Name,
                    "AccountGroupName" => null,
                    "Amount" => number_format($amountgroup, 2),
                    "Company" => $s->Company,
                    "CompanyName" => $s->CompanyCode,
                    "AccountGroup" => null,
                    // "FontWeight" => '500',
                    "Color" => 'e1ecfc'
                ];
                foreach ($accountgroups as $a) $result[] =$a;
            }

            return response()->json(
                $result,
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
                "hiddenField" => "CompanyName",
                'type' => 'combobox',
                'column' => '1/3',
                'validationParams' => 'required',
                'source' => 'company',
                'onClick' => [
                    'action' => 'request',
                    'store' => 'combosource/company',
                    'params' => null
                ]
            ],
            [
                'fieldToSave' => "Type",
                'type' => "combobox",
                'hiddenField'=> 'StatusName',
                'column' => "1/5",
                'source' => [],
                'store' => "",
                'default' => "All Accounts",
                'source' => [
                    ['Oid' => 'All', 'Name' => 'All Accounts'],
                    ['Oid' => 'NotZero', 'Name' => 'Only Transactions'],
                ]
            ],
            [
                'type' => 'action',
                'column' => '1/3'
            ]
        ];
    }

    public function historyFields()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w' => 0, 'r' => 0, 't' => 'text', 'n' => 'Oid', 'fs' => 'data.Oid'];
        $fields[] = ['w' => 120, 'r' => 0, 't' => 'text', 'n' => 'Type', 'fs' => 'JournalType.Code'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Code', 'fs' => 'data.Code'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'date', 'n' => 'Date', 'fs' => 'data.Date'];
        $fields[] = ['w' => 200, 'r' => 0, 't' => 'text', 'n' => 'Description', 'fs' => 'data.Description'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'double', 'n' => 'DebetBase', 'fs' => 'DebetBase'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'double', 'n' => 'CreditBase', 'fs' => 'CreditBase'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'BusinessPartner', 'fs' => 'BusinessPartner.Name'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Warehouse', 'fs' => 'Warehouse.Code'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Project', 'fs' => 'Project.Code'];
        $fields[] = ['w' => 0, 'h' => 1, 't' => 'text', 'n' => 'CashBank'];
        return $fields;
    }

    public function historyConfig(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->historyFields(), false, true);
        $fields[0]['cellRenderer'] = 'actionCell';
        return $fields;
    }

    public function historyList(Request $request)
    {
        try {
            $criteriaWhere = "";
            if ($request->has('Account')) {
                $criteriaWhere = $criteriaWhere." AND Account.Oid = '".$request->input('Account')."'";
            }
            if ($request->has('Company')) {
                $criteriaWhere = $criteriaWhere." AND Company.Oid = '".$request->input('Company')."'";
            }
            if ($request->has('DateStart')) {
                $datefrom = Carbon::parse($request->input('DateStart'));
                $criteriaWhere = $criteriaWhere." AND DATE_FORMAT(data.Date, '%Y-%m-%d') >= '".$datefrom."'";
            }
            if ($request->has('DateUntil')) {
                $dateto = Carbon::parse($request->input('DateUntil'));
                $criteriaWhere = $criteriaWhere." AND DATE_FORMAT(data.Date, '%Y-%m-%d') <= '".$dateto."'";
            }

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
                    JournalType.Code Type, data.Code, data.Date, data.Note Description, 
                    FORMAT(SUM(IFNULL(data.DebetBase,0)),2) DebetBase, FORMAT(SUM(IFNULL(data.CreditBase,0)),2) CreditBase, 
                    BusinessPartner.Name BusinessPartner, Warehouse.Code Warehouse, Project.Code Project
                    FROM accjournal data
                    LEFT OUTER JOIN accaccount Account ON data.Account = Account.Oid
                    LEFT OUTER JOIN sysjournaltype JournalType ON data.JournalType = JournalType.Oid
                    LEFT OUTER JOIN mstbusinesspartner BusinessPartner ON data.BusinessPartner = BusinessPartner.Oid
                    LEFT OUTER JOIN mstwarehouse Warehouse ON data.Warehouse = Warehouse.Oid
                    LEFT OUTER JOIN mstproject Project ON data.Project = Project.Oid
                    LEFT OUTER JOIN company Company On data.Company = Company.Oid
                    WHERE data.GCRecord IS NULL {$criteriaWhere}
                    GROUP BY data.PointOfSale, data.CashBank, data.GeneralJournal, data.PurchaseDelivery, data.PurchaseInvoice, data.SalesDelivery, data.SalesInvoice,
                    JournalType.Code, data.Code, data.Date, data.Note, BusinessPartner.Name, Warehouse.Code, Project.Code";
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
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
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

    public function historyPresearch(Request $request)
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
                    'term' => ''
                ]
            ],
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
                'type' => 'action',
                'column' => '1/6'
            ]
        ];
    }
}
