<?php

namespace App\AdminApi\Accounting\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Services\CashBankService;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Services\HttpService;
use App\Core\Base\Services\TravelAPIService;
use App\Core\Internal\Services\AutoNumberService;
use App\AdminApi\Pub\Controllers\PublicApprovalController;
use App\AdminApi\Pub\Controllers\PublicPostController;
use Carbon\Carbon;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\AdminApi\Development\Controllers\CRUDLinkController;

use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\CashBankDetail;
use App\Core\Accounting\Entities\Account;
use App\Core\Master\Entities\Currency;
use App\Core\Master\Entities\BankUser;
use App\Core\Internal\Entities\Status;
use App\Core\Accounting\Entities\Journal;
use App\Core\Master\Entities\BusinessPartnerGroupUser;
use App\Core\Trading\Entities\SalesOrder;
use App\Core\Trading\Entities\SalesInvoice;
use App\Core\Trading\Entities\PurchaseInvoice;
use App\Core\Travel\Entities\TravelTransaction;

use App\Core\Pub\Entities\PublicPost;
use App\Core\Pub\Entities\PublicComment;
use App\Core\Pub\Entities\PublicApproval;
use App\Core\Pub\Entities\PublicFile;
use App\Core\Master\Entities\Image;

class CashBankController extends Controller
{
    protected $roleService;
    protected $cashBankService;
    private $travelAPIService;
    private $publicPostController;
    private $autoNumberService;
    private $publicApprovalController;
    private $crudController;
    private $linkController;
    public function __construct(
        CashBankService $cashBankService,
        TravelAPIService $travelAPIService,
        AutoNumberService $autoNumberService,
        RoleModuleService $roleService
    ) {
        $this->module = 'acccashbank';
        $this->roleService = $roleService;
        $this->cashBankService = $cashBankService;
        $this->autoNumberService = $autoNumberService;
        $this->travelAPIService = $travelAPIService;
        $this->publicPostController = new PublicPostController(new RoleModuleService(new HttpService), new HttpService);
        $this->publicApprovalController = new PublicApprovalController($this->cashBankService);
        $this->crudController = new CRUDDevelopmentController();
        $this->linkController = new CRUDLinkController();
    }

    public function config(Request $request)
    {
        try {
            $data = $this->crudController->config($this->module);
            $data[0]->topButton = [
                [
                    'name' => 'Add New',
                    'icon' => 'PlusIcon',
                    'type' => 'open_form',
                    'url' => "cashbank/form"
                ],
                // [
                //     'name' => 'Add New Income',
                //     'icon' => 'PlusIcon',
                //     'type' => 'open_form',
                //     'url' => "cashbank/form?type=0"
                // ],
                // [
                //     'name' => 'Add New Expense',
                //     'icon' => 'PlusIcon',
                //     'type' => 'open_form',
                //     'url' => "cashbank/form?type=1"
                // ],
                // [
                //     'name' => 'Add New Receipt',
                //     'icon' => 'PlusIcon',
                //     'type' => 'open_form',
                //     'url' => "cashbank/form?type=2"
                // ],
                // [
                //     'name' => 'Add New Payment',
                //     'icon' => 'PlusIcon',
                //     'type' => 'open_form',
                //     'url' => "cashbank/form?type=3"
                // ],
                // [
                //     'name' => 'Add New Transfer',
                //     'icon' => 'PlusIcon',
                //     'type' => 'open_form',
                //     'url' => "cashbank/form?type=4"
                // ]
            ];
            return response()->json($data, Response::HTTP_OK);
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
                'type' => 'combobox',
                'column' => '1/6',
                'validationParams' => 'required',
                'source' => 'company',
                'hiddenField'=> 'CompanyName',
                'onClick' => [
                    'action' => 'request',
                    'store' => 'combosource/company',
                    'params' => null
                ]
            ],
            [
                'fieldToSave' => 'Account',
                'type' => 'autocomplete',
                'column' => '1/6',
                'validationParams' => 'required',
                'default' => null,
                'source' => [],
                'store' => 'autocomplete/account',
                'hiddenField'=> 'AccountName',
                'params' => [
                    'type' => 'combo',
                    'form' => 'cashbank',
                    'term' => ''
                ]
            ],
            [
                'fieldToSave' => 'DateFrom',
                'type' => 'inputdate',
                'column' => '1/6',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->startOfMonth()->format('Y-m-d')
            ],
            [
                'fieldToSave' => 'DateTo',
                'type' => 'inputdate',
                'column' => '1/6',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->endOfMonth()->format('Y-m-d')
            ],
            [
                "fieldToSave" => "Status",
                "type" => "combobox",
                'hiddenField'=> 'StatusName',
                "column" => "1/6",
                "source" => [
                    [
                        "Oid" => "All",
                        "Name" => "All"
                    ],
                    [
                        "Oid" => "Entry",
                        "Name" => "Entry"
                    ],
                    [
                        "Oid" => "Submit",
                        "Name" => "Submit"
                    ],
                    [
                        "Oid" => "Approved",
                        "Name" => "Approved"
                    ],
                    [
                        "Oid" => "Cancelled",
                        "Name" => "Cancelled"
                    ],
                    [
                        "Oid" => "Rejected",
                        "Name" => "Rejected"
                    ],
                    [
                        "Oid" => "Posted",
                        "Name" => "Posted Only"
                    ],
                    [
                        "Oid" => "Complete",
                        "Name" => "Complete Only"
                    ],
                ],
                "store" => "",
                "defaultValue" => "All"
            ],
            [
                'type' => 'action',
                'column' => '1/6'
            ]
        ];
    }

    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            $data = DB::table($this->module . ' as data');
            if ($request->has('Company')) {
                if ($request->input('Company') != 'null') $data = $data->whereRaw("data.Company = '" . $request->input('Company') . "'");
            }
            if ($request->has('Account')) {
                if ($request->input('Account') != 'null') $data->whereRaw("data.Account = '" . $request->input('Account') . "'");
            }
            if ($request->has('DateFrom')) {
                if ($request->input('DateFrom') != 'null') $data = $data->whereRaw("data.Date >= '" . $request->input('DateFrom') . "'");
            }
            if ($request->has('DateTo')) {
                if ($request->input('DateTo') != 'null') $data = $data->whereRaw("data.Date <= '" . $request->input('DateTo') . "'");
            }

            //PRESEARCH
            $type = $request->has('Status') ? $request->input('Status') : 'All';
            if ($type == 'Entry') $data = $data->whereIn('Status.Code', ['entry', 'requested']);
            if ($type == 'Submit') $data = $data->whereIn('Status.Code', ['submit']);
            if ($type == 'Approved') $data = $data->whereIn('Status.Code', ['posted', 'complete']);
            if ($type == 'Complete') $data = $data->whereIn('Status.Code', ['complete']);
            if ($type == 'Posted') $data = $data->whereIn('Status.Code', ['posted']);
            if ($type == 'Rejected') $data = $data->whereIn('Status.Code', ['rejected', 'reject']);
            if ($type == 'Cancelled') $data = $data->whereIn('Status.Code', ['cancel']);


            //filter authorized banks
            $check = BankUser::where('User', $user->Oid)->get();
            if ($check) {
                $banks = null;
                foreach ($check as $row) {
                    $account = Account::where('Bank', $row->Bank)->first();
                    $banks = $banks . ($banks ? "," : "") . "'" . $account->Oid . "'";
                }
                if ($banks) $data = $data->whereRaw("data.Account IN (" . $banks . ")");
            }

            //SECURITY FILTER COMPANY
            if ($user->CompanyAccess) {
                $data = $data->leftJoin('company AS CompanySecurity', 'CompanySecurity.Oid', '=', 'data.Company');
                $tmp = json_decode($user->CompanyAccess);
                $data = $data->whereIn('CompanySecurity.Code', $tmp);
            }

            // filter businesspartnergroupuser
            $businessPartnerGroupUser = BusinessPartnerGroupUser::select('BusinessPartnerGroup')->where('User', $user->Oid)->pluck('BusinessPartnerGroup');
            if ($businessPartnerGroupUser->count() > 0) $data->whereIn('BusinessPartner.BusinessPartnerGroup', $businessPartnerGroupUser);

            $data = $this->crudController->list($this->module, $data, $request);
            $role = $this->roleService->list('CashBank'); //rolepermission
            // foreach ($data as $row) $row->Action = $this->roleService->generateActionMaster($role);
            foreach ($data->data as $row) {
                $tmp = CashBank::where('Oid', $row->Oid)->first();
                if ($tmp) $row->Action = $this->action($tmp);
                $row->Role = $this->generateRole($row, $role);
            }
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = DB::table($this->module . ' as data');

            $data = $data->orderBy('Oid')->get();
            $role = $this->roleService->list('CashBank'); //rolepermission
            foreach ($data as $row) {
                $row->Date = Carbon::parse($row->Date)->format('Y-m-d');
                $row->StatusName = $row->StatusObj ? $row->StatusObj->Name : null;
                $row->Action = $this->roleService->generateActionMaster($role);
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    private function showSub($Oid)
    {
        // [{"Oid":0,"Name":"Income"},{"Oid":1,"Name":"Expense"},{"Oid":2,"Name":"Receipt"},{"Oid":3,"Name":"Payment"},{"Oid":4,"Name":"Transfer"}]
        $data = $this->crudController->detail($this->module, $Oid);
        if ($data->Type == 0) $data->TypeName = "Income";
        elseif ($data->Type == 1) $data->TypeName = "Expense";
        elseif ($data->Type == 2) $data->TypeName = "Receipt";
        elseif ($data->Type == 3) $data->TypeName = "Payment";
        elseif ($data->Type == 4) $data->TypeName = "Transfer";
        $data->Action = $this->action($data);
        foreach ($data->Details as $row) {
            $row->Action = $this->actionDetail($row);
        }
        return $data;
    }

    public function show(CashBank $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $user = Auth::user();                
                $data = $this->crudController->saving($this->module, $request, $Oid, false);

                //REQUEST CODE RANDOM
                if (!$Oid) {
                    if ($this->CashBankTypeApproval($data->Type)) {
                        //only new record, not using travel, & approvaltype
                        $data->Code = now()->format('ymdHis') . '-' . str_random(3);
                        if (!$data->RequestCode) $data->RequestCode = '<<Auto>>';
                        $data->RequestCode = $this->autoNumberService->generate($data, 'acccashbank', 'RequestCode');
                    } else {
                        $data->RequestCode = now()->format('ymdHis') . '-' . str_random(3);
                    }                    
                }

                $account = Account::with('CurrencyObj')->findOrFail($data->Account);
                $cur = $account->CurrencyObj;
                if (isset($request->Type)) $data->Type = $request->Type;
                $data->Currency = $account->Currency;
                $data->Rate = $data->Rate ?: $account->CurrencyObj->getRate($data->Date)->MidRate;
                $data->Status = $data->Status ?: Status::entry()->first()->Oid;
                // $data->TotalAmount = $data->SubtotalAmount - $data->DiscountAmount + $data->AdditionalAmount + $data->PrepaidAmount;
                $data->TotalBase = $cur->toBaseAmount($data->TotalAmount, $data->Rate);
                // $data->TotalAmountWording = convert_number_to_words($data->TotalAmount);
                $data->save();

                $this->publicPostController->sync($data, 'CashBank');
                if (isset($data->Department) && !in_array($data->StatusObj->Code, ['submit', 'post', 'posted', 'cancel'])) $this->publicApprovalController->formCreate($data, 'CashBank');

                $this->updateTotal($data);
                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('CashBank'); //rolepermission
            $data = $this->showSub($data->Oid);
            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(CashBank $data)
    {
        try {
            DB::transaction(function () use ($data) {
                //delete
                $delete = PublicApproval::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = Image::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicComment::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicFile::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicPost::where('Oid', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = CashBankDetail::where('CashBank', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $data->delete();
            });
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function CashBankTypeApproval($type)
    {
        $company = Auth::user()->CompanyObj;
        $isApproval = false;
        if ($company->CustomFieldSetting) {
            $data = json_decode($company->CustomFieldSetting);
            foreach($data as $row) {
                if (isset($row->table) && isset($row->type) && isset($row->approval)) if ($row->table == 'acccashbank' && $row->type == 'module') $isApproval = $row->approval;
            }
        }
        return in_array($type, [1, 3, 4,"1", "3", "4"]) && $isApproval;
    }

    public function action(CashBank $data)
    {
        $url = 'cashbank';
        $actionOpen = [
            'name' => 'Open',
            'icon' => 'ViewIcon',
            'type' => 'open_form',
            'url' => $url . '/form?item={Oid}',
            // 'url' => $url . '/form?item={Oid}&type=' . ($data->Type ?: 0),
        ];
        $actionPost = [
            'name' => 'Change to Posted',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/post',
        ];
        $actionUnpost = [
            'name' => 'Change to Entry',
            'icon' => 'UnlockIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/unpost',
        ];
        $actionCancelled = [
            'name' => 'Change to Cancel',
            'icon' => 'XIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/cancelled',
        ];
        $actionGenerateLOA = [
            'name' => 'Generate LOA',
            'icon' => 'PrinterIcon',
            'type' => 'confirm',
            'post' => 'travelapi/payment/prereport/{Oid}',
        ];
        // $actionPrintprereport = [
        //     'name' => 'Print Standard 1',
        //     'icon' => 'PrinterIcon',
        //     'type' => 'open_report',
        //     'hide' => true,
        //     'get' => 'prereport/cashbank?oid={Oid}&report=cashbank',
        //     'afterRequest' => 'init'
        // ];
        $actionPrint = [
            'name' => 'Print',
            'icon' => 'PrinterIcon',
            'type' => 'global_form',
            'hide' => false,
            'open_report' => true,
            'post' => 'prereport/' . $url . '?oid={Oid}',
            'afterRequest' => 'init',
            "form" => [
                [
                    'fieldToSave' => "Report",
                    'overrideLabel' => "Report Format",
                    'type' => "combobox",
                    'hiddenField'=> 'ReportName',
                    'column' => "1/2",
                    'source' => [],
                    'store' => "",
                    'source' => [
                        ['Oid' => 'cashbank', 'Name' => 'Print Standard 1'],
                        ['Oid' => 'prereport', 'Name' => 'Print Standard 2'],
                        ['Oid' => 'paymentrequest', 'Name' => 'Payment Request'],
                    ]
                ],
                [
                    'fieldToSave' => "PaperSize",
                    'overrideLabel' => "Paper Size",
                    'type' => "combobox",
                    'hiddenField'=> 'ReportName',
                    'column' => "1/2",
                    'source' => [],
                    'store' => "",
                    'default' => "A4",
                    'source' => [
                        ['Oid' => 'A4', 'Name' => 'A4'],
                        ['Oid' => 'Half', 'Name' => 'Half Continuous'],
                        ['Oid' => 'Full', 'Name' => 'Full Continuous'],
                    ]
                ]
            ]
        ];
        // $actionPrintprereportBKK = [
        //     'name' => 'Print Standard 2',
        //     'icon' => 'PrinterIcon',
        //     'type' => 'open_report',
        //     'hide' => true,
        //     'get' => 'prereport/cashbank?oid={Oid}&report=prereport',
        //     'afterRequest' => 'init'
        // ];
        // $printprereportpr = [
        //     'name' => 'Print Payment Request',
        //     'icon' => 'PrinterIcon',
        //     'type' => 'open_report',
        //     'hide' => true,
        //     'get' => 'prereport/cashbank?oid={Oid}&report=paymentrequest',
        //     'afterRequest' => 'init'
        // ];
        // $printreportreceipt = [
        //     'name' => 'Print Official Receipt',
        //     'icon' => 'PrinterIcon',
        //     'type' => 'open_report',
        //     'hide' => true,
        //     'get' => 'prereport/cashbank?oid={Oid}&report=receipt',
        //     'afterRequest' => 'init'
        // ];
        $printreportreceipt = [
            'name' => 'Print Official Receipt',
            'icon' => 'PrinterIcon',
            'type' => 'global_form',
            'hide' => false,
            'open_report' => true,
            'post' => 'prereport/' . $url . '?oid={Oid}',
            'afterRequest' => 'init',
            "form" => [
                [
                    'fieldToSave' => "Report",
                    'overrideLabel' => "Report Format",
                    'type' => "inputtext",
                    'hiddenField'=> 'ReportName',
                    'column' => "1/2",
                    'default' => "receipt",
                    'disabled' => true,
                ],
                [
                    'fieldToSave' => "PaperSize",
                    'overrideLabel' => "Paper Size",
                    'type' => "inputtext",
                    'hiddenField'=> 'ReportName',
                    'column' => "1/2",
                    'default' => "receipt",
                    'disabled' => true,
                ]
            ]
        ];
        $actionViewJournal = [
            'name' => 'View Journal',
            'icon' => 'BookOpenIcon',
            'type' => 'open_grid',
            'get' => 'journal?' . $url . '={Oid}',
        ];
        $actionDelete = [
            'name' => 'Delete',
            'icon' => 'TrashIcon',
            'type' => 'confirm',
            'delete' => $url . '/{Oid}'
        ];
        $actionEditCodeNote = [
            "name" => "Edit Code & Note",
            "icon" => "ActivityIcon",
            "type" => "global_form",
            "showModal" => false,
            "get" => "cashbank/{Oid}",
            "post" => "cashbank/{Oid}",
            "afterRequest" => "apply",
            "form" => [
                [
                    'fieldToSave' => "Code",
                    'type' => "inputtext"
                ],
                [
                    'fieldToSave' => "CodeReff",
                    'type' => "inputtext"
                ],
                [
                    'fieldToSave' => "Note",
                    'type' => "inputarea"
                ],
            ]
        ];
        $actionSubmit = $this->publicApprovalController->formAction($data, 'CashBank', 'submit');
        $actionRequest = $this->publicApprovalController->formAction($data, 'CashBank', 'request');
        $return = [];
        switch ($data->StatusObj->Code) {
            case "":
                $return[] = $actionOpen;
                if (!$this->CashBankTypeApproval($data->Type)) $return[] = $actionPost;
                $return[] = $actionCancelled;
                // $return[] = $actionDelete;
                break;
            case "posted":
                $return[] = $actionOpen;
                $return[] = $actionEditCodeNote;
                $return[] = $actionGenerateLOA;
                $return[] = $actionPrint;
                // $return[] = $actionPrintprereport;
                // $return[] = $actionPrintprereportBKK;
                // $return[] = $printprereportpr;
                $return[] = $printreportreceipt;
                $return[] = $actionViewJournal;
                $return[] = $actionUnpost;
                break;
            case "request":
                $return[] = $actionOpen;
                $return[] = $actionCancelled;
                $return[] = $actionPrint;
                // $return[] = $actionPrintprereport;
                // $return[] = $actionPrintprereportBKK;
                // $return[] = $printprereportpr;
                $return[] = $printreportreceipt;
                if (!$this->CashBankTypeApproval($data->Type)) {
                    $return[] = $actionPost;
                } else {
                    $return[] = $actionRequest;
                    $return[] = $actionSubmit;
                }                
                // $return[] = $actionDelete;
                break;
            case "entry":
                $return[] = $actionOpen;
                $return[] = $actionCancelled;
                $return[] = $actionPrint;
                // $return[] = $actionPrintprereport;
                // $return[] = $actionPrintprereportBKK;
                // $return[] = $printprereportpr;
                $return[] = $printreportreceipt;
                if (!$this->CashBankTypeApproval($data->Type)) {
                    $return[] = $actionPost;
                } else {
                    $return[] = $actionRequest;
                    $return[] = $actionSubmit;
                }                
                // $return[] = $actionDelete;
                break;
            case "submit":
                $return = $this->publicApprovalController->formAction($data, 'CashBank', 'approval');
                $return[] = $actionUnpost;
                $return[] = $actionPrint;
                // $return[] = $actionPrintprereport;
                // $return[] = $actionPrintprereportBKK;
                // $return[] = $printprereportpr;
                $return[] = $printreportreceipt;
                break;
        }
        $return = actionCheckCompany($this->module, $return);
        return $return;
    }


    public function actionlist()
    {
        $url = 'cashbank';
        $actionOpen = [
            'name' => 'Open',
            'icon' => 'ArrowUpRightIcon',
            'type' => 'open_form',
            'url' => $url . '?Account={Oid}',
        ];
        $actionAddNew = [
            'name' => 'Add New ',
            'icon' => 'PlusIcon',
            'type' => 'open_form',
            'url' => $url . '/form?Account={Oid}',
        ];
        // $actionAddNewIncome = [
        //     'name' => 'Add New Income',
        //     'icon' => 'PlusIcon',
        //     'type' => 'open_form',
        //     'url' => $url . '/form?type=0&Account={Oid}',
        // ];
        // $actionAddNewExpense = [
        //     'name' => 'Add New Expense',
        //     'icon' => 'PlusIcon',
        //     'type' => 'open_form',
        //     'url' => $url . '/form?type=1&Account={Oid}',
        // ];
        // $actionAddNewReceipt = [
        //     'name' => 'Add New Receipt',
        //     'icon' => 'PlusIcon',
        //     'type' => 'open_form',
        //     'url' => $url . '/form?type=2&Account={Oid}',
        // ];
        // $actionAddNewPayment = [
        //     'name' => 'Add New Payment',
        //     'icon' => 'PlusIcon',
        //     'type' => 'open_form',
        //     'url' => $url . '/form?type=3&Account={Oid}',
        // ];
        // $actionAddNewTransfer = [
        //     'name' => 'Add New Transfer',
        //     'icon' => 'PlusIcon',
        //     'type' => 'open_form',
        //     'url' => $url . '/form?type=4&Account={Oid}',
        // ];
        $return = [];
        $return[] = $actionOpen;
        $return[] = $actionAddNew;
        // $return[] = $actionAddNewIncome;
        // $return[] = $actionAddNewExpense;
        // $return[] = $actionAddNewReceipt;
        // $return[] = $actionAddNewPayment;
        // $return[] = $actionAddNewTransfer;
        return $return;
    }

    public function createdetail(Request $request)
    {
        $transaction = false;
        if ($request->has('transaction')) $transaction = true;
        $cashBank = $request->input('CashBank');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));

        try {
            $data = new CashBankDetail();
            DB::transaction(function () use ($request, &$data, $transaction, $cashBank) {
                $cashBank = CashBank::findOrFail($cashBank);
                $disabled = array_merge(disabledFieldsForEdit(), ['AccountName', 'Amount', 'TravelPurchaseInvoiceName', 'TravelSalesTransactionName', 'CurrencyName', 'TravelSalesTransactionDetailName', 'APInvoiceName', 'ARInvoiceName', 'PurchaseInvoiceName', 'SalesInvoiceName', 'PurchaseRequestName', 'TruckingPrimeMoverObj', 'TruckingPrimeMoverName']);
                $data = $this->crudController->save('acccashbankdetail', $data, $request, $cashBank);

                if ($data->Sequence == 0 || !$data->Sequence) {
                    try {
                        $sequence = (CashBankDetail::where('CashBank',$cashBank->Oid)->whereNotNull('Sequence')->max('Sequence') ?: 0)+1;
                    } 
                    catch (\Exception $ex) {  $err = true; }
                    $data->Sequence = $sequence;
                    $data->save();
                }

                $data->CashBank = $cashBank->Oid;
                $data->Company = $cashBank->Company;
                $account = Account::with('CurrencyObj')->findOrFail($cashBank->Account);
                $data->Currency = $account->Currency;
                $curCashBank = $account->CurrencyObj;
                $curInvoice = Currency::findOrFail($data->Currency);
                if ($data->Currency == $cashBank->Currency) {
                    $amountCashBank = $request->Amount;
                    $amountCashBankBase = $curCashBank->toBaseAmount($amountCashBank, $data->Rate);
                } else {
                    $amountCashBank = $request->Amount;
                    $amountCashBankBase = $curCashBank->toBaseAmount($amountCashBank, $data->Rate);
                }
                $data->AmountInvoice = $request->Amount;
                $data->AmountInvoiceBase = $curInvoice->toBaseAmount($request->Amount, $data->Rate);
                $data->AmountCashBank = $amountCashBank;
                $data->AmountCashBankBase = $amountCashBankBase;

                $data->save();

                if ($data->SalesInvoice) $this->linkController->SalesInvoiceCalculateOutstanding($data->SalesInvoice);
                elseif ($data->PurchaseInvoice) $this->linkController->PurchaseInvoiceCalculateOutstanding($data->PurchaseInvoice);
                elseif ($data->TravelTransactionDetail) $this->linkController->TravelTransactionCalculateOutstandingReceipt($data->TravelTransactionDetail);

                $this->updateTotal($cashBank);                
                $data->ParentObj = [
                    'TotalAmount' => $cashBank->TotalAmount
                ];

                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            $account = Account::where('Oid',$data->Account)->first();
            $data->AccountName = isset($account) ? $account->Name : null;

            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function savedetail(Request $request)
    {
        $transaction = false;
        if ($request->has('transaction')) $transaction = true;
        try {
            if ($request->input('Oid')) $data = CashBankDetail::where('Oid', $request->input('Oid'))->first();
            if (!$data) $data = new CashBankDetail();
            DB::transaction(function () use ($request, &$data, $transaction) {
                $cashBank = CashBank::findOrFail($request->input('CashBank'));
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));

                if (isset($request->AmountInvoice)) $request->AmountInvoice = str_replace(",", "", $request->AmountInvoice);
                if (isset($request->AmountCashBank)) $request->AmountCashBank = str_replace(",", "", $request->AmountCashBank);

                $data = $this->crudController->save('acccashbankdetail', $data, $request, $cashBank);
                if ($data->Sequence == 0 || !$data->Sequence) {
                    try {
                        $sequence = (CashBankDetail::where('CashBank',$cashBank->Oid)->whereNotNull('Sequence')->max('Sequence') ?: 0 + 1);
                    } 
                    catch (\Exception $ex) {  $err = true; }
                    $data->Sequence = $sequence;
                    $data->save();
                }

                $account = Account::with('CurrencyObj')->findOrFail($cashBank->Account);
                $curCashBank = $account->CurrencyObj;
                $data->Currency = isset($data->Currency) ? $data->Currency : $cashBank->Currency;
                $curInvoice = Currency::findOrFail($data->Currency);
                if ($data->Currency == $cashBank->Currency) {
                    $amountCashBank = $request->AmountInvoice;
                    $amountCashBankBase = $curCashBank->toBaseAmount($amountCashBank, $data->Rate);
                } else {
                    $amountCashBank = isset($request->AmountCashBank) ? $request->AmountCashBank : $request->AmountInvoice;
                    $amountCashBankBase = $curCashBank->toBaseAmount($amountCashBank, $data->Rate);
                }
                $data->AmountInvoice = $request->AmountInvoice;
                $data->AmountInvoiceBase = $curInvoice->toBaseAmount($request->AmountInvoice, $data->Rate);
                $data->AmountCashBank = $amountCashBank;
                $data->AmountCashBankBase = $amountCashBankBase;
                $data->save();

                if ($data->SalesInvoice) $this->linkController->SalesInvoiceCalculateOutstanding($data->SalesInvoice);
                elseif ($data->PurchaseInvoice) $this->linkController->PurchaseInvoiceCalculateOutstanding($data->PurchaseInvoice);
                elseif ($data->TravelTransactionDetail) $this->linkController->TravelTransactionCalculateOutstandingReceipt($data->TravelTransactionDetail);

                $this->updateTotal($cashBank);
                $data->AccountName = $data->Accountobj ? $data->Accountobj->Name : null;
                $data->ParentObj = [
                    'TotalAmount' => $cashBank->TotalAmount
                ];
                if (!$data) throw new \Exception('Data is failed to be saved');
            });
            
            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function listcashbankconfig(Request $request)
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = serverSideConfigField('Code');
        $fields[] = serverSideConfigField('Name');
        $fields[] = ['w' => 250, 'h' => 0, 't' => 'double', 'n' => 'BalanceAmount'];

        $fields = $this->crudController->jsonConfig($fields);
        foreach ($fields as &$row) if ($row['headerName'] == 'AccountSection') $row['source'] = comboSelect('accaccountsection');
        $fields[0]['cellRenderer'] = 'actionCell';
        return $fields;
    }

    public function listcashbank(Request $request)
    {
        //filter authorized banks
        $user = Auth::user();
        $banks = null;
        $check = BankUser::where('User', $user->Oid)->get();
        if ($check) {
            foreach ($check as $row) {
                $account = Account::where('Bank', $row->Bank)->first();
                $banks = $banks . ($banks ? "," : "") . "'" . $account->Oid . "'";
            }
        }
        if ($banks) $banks = " AND a.Oid IN (" . $banks . ") AND b.Oid IS NOT NULL ";

        $query = "SELECT a.Oid, a.Code, a.Name, SUM(IFNULL(j.DebetAmount,0)- IFNULL(j.CreditAmount,0)) AS BalanceAmount
                  FROM accaccount a
                  LEFT OUTER JOIN sysaccounttype act ON a.AccountType = act.Oid
                  LEFT OUTER JOIN accjournal j ON a.Oid = j.Account
                  LEFT OUTER JOIN mstbank b ON b.Oid = a.Bank
                  WHERE act.Code IN ('Cash','Bank') AND j.GCRecord IS NULL AND a.GCRecord IS NULL {$banks}
                  GROUP BY a.Oid, a.Code, a.Name";
        // dd($query);
        $data = DB::select($query);
        foreach ($data as $row) $row->Action = $this->actionlist();

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function invoicedelete(Request $request, CashBankDetail $data)
    {
        try {
            DB::transaction(function () use ($data, $request) {
                if ($data->SalesInvoice) {
                    $invoice = $data->SalesInvoice;
                    $data->delete();
                    $this->linkController->SalesInvoiceCalculateOutstanding($invoice);
                } elseif ($data->PurchaseInvoice) {
                    $invoice = $data->PurchaseInvoice;
                    $data->delete();
                    $this->linkController->PurchaseInvoiceCalculateOutstanding($invoice);
                } elseif ($data->TravelTransactionDetail) {
                    $invoice = $data->TravelTransactionDetail;
                    $data->delete();
                    $this->linkController->TravelTransactionCalculateOutstandingReceipt($invoice);
                } else {
                    $data->delete();
                }
                $this->updateTotal($data->CashBankObj);
            });
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function post(Request $request, CashBank $data)
    {
        try {
            $company = Auth::user()->CompanyObj;

            $this->cashBankService->post($data->Oid);
            $this->publicPostController->sync($data, 'CashBank');

            if ($company->ModuleTravel && $data->Type == 3) {
                $this->travelAPIService->setToken($request->bearerToken());
                $this->travelAPIService->postapi("/api/travel/v1/adminapi/cashbank/payment/prereport/" . $data->Oid . "?system=" . $company->Oid);
            }
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    public function unpost(CashBank $data)
    {
        try {
            $this->cashBankService->unpost($data->Oid);
            $this->publicApprovalController->formApprovalReset($data);
            $this->publicPostController->sync($data, 'CashBank');
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    public function cancelled(CashBank $data)
    {
        try {
            $this->cashBankService->cancelled($data->Oid);
            $this->publicPostController->sync($data, 'CashBank');
            $tmp = collect($data->Details)->pluck('PurchaseInvoice');
            if ($tmp) $this->linkController->PurchaseInvoiceCalculateOutstanding($tmp);
            $tmp = collect($data->Details)->pluck('SalesInvoice');
            if ($tmp) $this->linkController->SalesInvoiceCalculateOutstanding($tmp);
            $tmp = collect($data->Details)->pluck('TravelTransaction');
            if ($tmp) $this->linkController->TravelTransactionCalculateOutstandingReceipt($tmp);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function journal(CashBank $data)
    {
        try {
            return Journal::where('CashBank', $data->Oid);
            // return $data->Journals();  
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function reconcile(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $date = $request->has('date') ? $request->input('date') : now()->addHours(company_timezone())->toDateTimeString();
                $user = Auth::user();
                $data = CashBank::whereIn('Oid', $request->cashbank)->get();

                foreach ($data as $row) {
                    $row->ReconcileDate = $date;
                    $row->ReconcileUser = $user->Oid;
                    $row->save();
                }
            });

            $data = CashBank::whereIn('Oid', $request->cashbank)->get();
            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function unreconcile(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $data = CashBank::whereIn('Oid', $request->cashbank)->get();
                foreach ($data as $row) {
                    $row->ReconcileDate = null;
                    $row->ReconcileUser = null;
                    $row->save();
                }
            });

            $data = CashBank::whereIn('Oid', $request->cashbank)->get();
            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function balanceCashBank(Account $data)
    {
        try {
            $query = "SELECT ac.Account, 
                    SUM(
                    (CASE WHEN ac.ReconcileDate IS NULL THEN 0 ELSE ac.TotalAmount END) *
                    (CASE WHEN ac.Type=0 THEN 1 
                    WHEN ac.Type=1 THEN -1 
                    WHEN ac.Type=2 THEN 1 
                    WHEN ac.Type=3 THEN -1
                    WHEN ac.Type=4 THEN 1 END)
                    ) AS TotalAmountUnreconciled,
                    SUM(
                    (CASE WHEN ac.ReconcileDate IS NULL THEN ac.TotalAmount ELSE 0 END) *
                    (CASE WHEN ac.Type=0 THEN 1 
                    WHEN ac.Type=1 THEN -1
                    WHEN ac.Type=2 THEN 1
                    WHEN ac.Type=3 THEN -1
                    WHEN ac.Type=4 THEN 1 END)
                    ) AS TotalAmountReconciled
                    FROM acccashbank ac
                    LEFT OUTER JOIN sysstatus s ON ac.Status = s.Oid 
                    WHERE s.Code = 'posted' AND ac.Account = '{$data->Oid}'
                    GROUP BY ac.Account";
            $data = DB::select($query);

            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function invoiceSearch(Request $request)
    {
        switch ($request->input('type')) {
            case 2: //receipt
                if ($request->input('form') == 'salesinvoice') {
                    $query = "SELECT  pc.Oid, 
                    CONCAT(pc.Code, '  (', DATE_FORMAT(pc.Date, '%Y-%m-%d'),'):  ', c.Code,' ',(IFNULL(pc.TotalAmount,0) - IFNULL(pc.PrepaidAmount,0) - IFNULL(pc.PaidAmount,0))) AS Description, 
                    c.Oid AS Currency, (IFNULL(pc.TotalAmount,0) - IFNULL(pc.PrepaidAmount,0) - IFNULL(pc.PaidAmount,0)) AS AmountInvoice, pc.Rate
                    FROM trdsalesinvoice pc
                    LEFT OUTER JOIN mstbusinesspartner bp ON pc.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstcurrency c ON pc.Currency = c.Oid
                    LEFT OUTER JOIN sysstatus s ON pc.Status = s.Oid
                    WHERE (IFNULL(pc.TotalAmount,0) - IFNULL(pc.PrepaidAmount,0) - IFNULL(pc.PaidAmount,0)) > 0
                    AND pc.GCRecord IS NULL
                    AND pc.Oid NOT IN ({$request->input('exception')})
                    AND pc.Company = '{$request->input('company')}'
                    AND pc.BusinessPartner = '{$request->input('businesspartner')}'
                    AND DATE_FORMAT(pc.Date, '%Y-%m-%d') <= '{$request->input('date')}'
                    AND s.Code = 'posted'";
                    break;
                }
                if ($request->input('form') == 'salesorder') {
                    $query = "SELECT so.Oid, 
                    CONCAT(so.Code, '  (', DATE_FORMAT(so.Date, '%Y-%m-%d'),'):  ', c.Code,' ',(IFNULL(so.TotalAmount,0) - IFNULL(so.PaidAmount,0))) AS Description, 
                    c.Oid AS Currency, (IFNULL(so.TotalAmount,0) - IFNULL(so.PaidAmount,0)) AS AmountInvoice, so.Rate
                    FROM trdsalesorder so
                    LEFT OUTER JOIN mstbusinesspartner bp ON so.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstcurrency c ON c.Oid = so.Currency
                    LEFT OUTER JOIN sysstatus s ON so.Status = s.Oid
                    WHERE (IFNULL(so.TotalAmount,0) - IFNULL(so.PaidAmount,0)) > 0
                    AND so.GCRecord IS NULL
                    AND so.Oid NOT IN ({$request->input('exception')})
                    AND so.Company = '{$request->input('company')}'
                    AND so.BusinessPartner = '{$request->input('businesspartner')}'
                    AND DATE_FORMAT(so.Date, '%Y-%m-%d') <= '{$request->input('date')}'
                    AND s.Code = 'posted'";
                    break;
                }
            case 3: //payment
                if ($request->input('form') == 'traveltransaction') { // jika langsung ambil dari transakasi
                    $query = "SELECT d.Oid, CONCAT(d.Code, '  (', DATE_FORMAT(p.Date, '%Y-%m-%d'),'):  ', c.Code,' ',(IFNULL(d.PurchaseSubtotal,0) - IFNULL(d.PaidAmount,0))) AS Description,
                        c.Oid AS Currency, (IFNULL(d.PurchaseSubtotal,0) - IFNULL(d.PaidAmount,0)) AS AmountInvoice, d.PurchaseRate AS Rate
                        FROM pospointofsale p
                        LEFT OUTER JOIN traveltransaction t ON t.Oid = p.Oid
                        LEFT OUTER JOIN trvtransactiondetail d ON d.TravelTransaction = t.Oid
                        LEFT OUTER JOIN mstbusinesspartner bp ON d.BusinessPartner = bp.Oid
                        LEFT OUTER JOIN mstcurrency c ON d.PurchaseCurrency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        WHERE (IFNULL(d.SalesSubtotal,0) - IFNULL(d.PaidAmount,0)) > 0
                        AND p.GCRecord IS NULL
                        AND d.Oid NOT IN ({$request->input('exception')})
                        AND p.Company = '{$request->input('company')}'
                        AND d.BusinessPartner = '{$request->input('businesspartner')}'
                        AND DATE_FORMAT(p.Date, '%Y-%m-%d') <= '{$request->input('date')}'";
                    // by eka 20200316 blm filter status dikarenakan bisa jd blm paid udh hrs byr vendor
                    break;
                }
                if ($request->input('form') == 'purchaseorder') { // jika langsung ambil dari transakasi
                    $query = "SELECT po.Oid, 
                            CONCAT(po.Code, '  (', DATE_FORMAT(po.Date, '%Y-%m-%d'),'):  ', c.Code,' ',(IFNULL(po.TotalAmount,0) - IFNULL(po.PaidAmount,0))) AS Description, 
                            c.Oid AS Currency, (IFNULL(po.TotalAmount,0) - IFNULL(po.PaidAmount,0)) AS AmountInvoice, po.Rate
                            FROM trdpurchaseorder po
                            LEFT OUTER JOIN mstbusinesspartner bp ON po.BusinessPartner = bp.Oid
                            LEFT OUTER JOIN mstcurrency c ON c.Oid = po.Currency
                            LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
                            WHERE (IFNULL(po.TotalAmount,0) - IFNULL(po.PaidAmount,0)) > 0
                            AND po.GCRecord IS NULL 
                            AND po.Oid NOT IN ({$request->input('exception')})
                            AND po.Company = '{$request->input('company')}'
                            AND po.BusinessPartner = '{$request->input('businesspartner')}'
                            AND DATE_FORMAT(po.Date, '%Y-%m-%d') <= '{$request->input('date')}'
                            AND s.Code = 'posted'";
                    // by eka 20200316 blm filter status dikarenakan bisa jd blm paid udh hrs byr vendor
                    break;
                } else {
                    $query = "SELECT  pc.Oid, 
                        CONCAT(pc.Code, '  (', DATE_FORMAT(pc.Date, '%Y-%m-%d'),'):  ', c.Code,' ',(IFNULL(pc.TotalAmount,0) - IFNULL(pc.PrepaidAmount,0) - IFNULL(pc.PaidAmount,0))) AS Description, 
                        c.Oid AS Currency, (IFNULL(pc.TotalAmount,0) - IFNULL(pc.PrepaidAmount,0) - IFNULL(pc.PaidAmount,0)) AS AmountInvoice, pc.Rate
                        FROM trdpurchaseinvoice pc
                        LEFT OUTER JOIN mstbusinesspartner bp ON pc.BusinessPartner = bp.Oid
                        LEFT OUTER JOIN mstcurrency c ON pc.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON pc.Status = s.Oid
                        WHERE (IFNULL(pc.TotalAmount,0) - IFNULL(pc.PrepaidAmount,0) - IFNULL(pc.PaidAmount,0)) > 0
                        AND pc.GCRecord IS NULL
                        AND pc.Oid NOT IN ({$request->input('exception')})
                        AND pc.Company = '{$request->input('company')}'
                        AND pc.BusinessPartner = '{$request->input('businesspartner')}'
                        AND DATE_FORMAT(pc.Date, '%Y-%m-%d') <= '{$request->input('date')}'
                        AND s.Code = 'posted'";
                    break;
                }
        }
        $data = DB::select($query);
        foreach ($data as $row) {
            $cur = Currency::findOrFail($row->Currency);
            $row->AmountCashBank = $cur->convertRate($request->input('currency'), $row->AmountInvoice);
        }
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function invoiceAdd(Request $request)
    {
        try {
            $cashBank = CashBank::findOrFail($request->input('oid'));
            $type = $request->input('type');
            $form = $request->has('form') ? $request->input('form') : 'invoice';
            $details = [];
            $arr = [];
            $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
            DB::transaction(function () use ($request, &$details, $cashBank, $type, $form) {
                $string = "";
                foreach ($request as $row) $string = ($string ? $string . "," : null) . "'" . $row . "'";
                switch ($type) {
                    case 2: // receipt
                        if ($form == 'salesinvoice') {
                            $data = SalesInvoice::whereIn('Oid', $request)->whereRaw("(IFNULL(TotalAmount,0) - IFNULL(PrepaidAmount,0) - IFNULL(PaidAmount,0)) > 0")->get();
                            foreach($data as $row) $row->OutstandingAmount = $row->TotalAmount - $row->PrepaidAmount - $row->PaidAmount;
                            break;
                        } elseif ($form == 'salesorder') { // jika langsung ambil dari transakasi
                            $data = SalesOrder::whereIn('Oid', $request)->whereRaw("IFNULL(TotalAmount,0) - IFNULL(PrepaidAmount,0) > 0")->get();
                            foreach($data as $row) $row->OutstandingAmount = $row->TotalAmount - $row->PaidAmount;
                            break;
                        }
                    case 3: //payment
                        if ($form == 'traveltransaction') { // jika langsung ambil dari transakasi
                            $query = "SELECT d.*, c.Oid AS Currency, c.Code AS CurrencyCode, bpag.PurchaseInvoice AS Account, 
                                (IFNULL(d.SalesSubtotal,0) - IFNULL(d.PaidAmount,0)) AS OutstandingAmount
                                FROM pospointofsale p
                                LEFT OUTER JOIN traveltransaction t ON t.Oid = p.Oid
                                LEFT OUTER JOIN trvtransactiondetail d ON d.TravelTransaction = t.Oid
                                LEFT OUTER JOIN mstbusinesspartner bp ON d.BusinessPartner = bp.Oid
                                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpg.BusinessPartnerAccountGroup = bpag.Oid
                                LEFT OUTER JOIN mstcurrency c ON d.PurchaseCurrency = c.Oid
                                LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                                WHERE (IFNULL(d.SalesSubtotal,0) - IFNULL(d.PaidAmount,0)) > 0
                                AND p.GCRecord IS NULL AND d.Oid IN (" . $string . ")
                                ";
                            break;
                        } elseif ($form == 'purchaseorder') { // jika langsung ambil dari transakasi
                            $query = "SELECT pod.*, c.Oid AS Currency, c.Code AS CurrencyCode, bpag.PurchaseInvoice AS Account,
                                po.Code AS Code,
                                (IFNULL(po.TotalAmount,0) - IFNULL(po.PaidAmount,0)) AS OutstandingAmount
                                FROM trdpurchaseorder po
                                LEFT OUTER JOIN trdpurchaseorderdetail pod ON po.Oid = pod.PurchaseOrder
                                LEFT OUTER JOIN mstbusinesspartner bp ON po.BusinessPartner = bp.Oid
                                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpg.BusinessPartnerAccountGroup = bpag.Oid
                                LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
                                LEFT OUTER JOIN mstcurrency c ON c.Oid = po.Currency
                                WHERE (IFNULL(po.TotalAmount,0) - IFNULL(po.PaidAmount,0)) > 0
                                AND po.GCRecord IS NULL AND pod.Oid IN (" . $string . ")
                                ";
                            break;
                        } else {
                            $data = PurchaseInvoice::whereIn('Oid', $request)->whereRaw("(IFNULL(TotalAmount,0) - IFNULL(PrepaidAmount,0) - IFNULL(PaidAmount,0)) > 0")->get();
                            foreach($data as $row) $row->OutstandingAmount = $row->TotalAmount - $row->PrepaidAmount - $row->PaidAmount;
                            break;
                        }
                }
                // $data = DB::select($query);
                foreach ($data as $row) {
                    $curCashBank = $cashBank->AccountObj->CurrencyObj;
                    $curInvoice = Currency::findOrFail($row->Currency);
                    $rate = $curCashBank->getRate($cashBank->Date);
                    $rate = $rate != null ? $rate->MidRate : 1;
                    if ($curCashBank->Oid == $row->Currency) {
                        $amountCashBank = $row->OutstandingAmount;
                        $amountCashBankBase = $curCashBank->toBaseAmount($row->OutstandingAmount, $rate);
                    } else {
                        $amountCashBank = $curInvoice->convertRate($curCashBank->Oid, $row->OutstandingAmount, $cashBank->Date);
                        $amountCashBankBase = $curInvoice->toBaseAmount($amountCashBank, $rate);
                    }

                    $detail = new CashBankDetail();
                    $detail->CashBank = $cashBank->Oid;
                    $detail->Company = $cashBank->Company;
                    if ($type == 2) {
                        if ($form == 'salesinvoice') {
                            $detail->SalesInvoice = $row->Oid;
                            $detail->Description = 'Invoice ' . $row->Code . ' ' . $row->Date;
                            // $row->PaidAmount = $row->TotalAmount;
                            // $row->save();
                        } elseif ($form == 'salesorder') {
                            $detail->SalesOrder = $row->Oid;
                            $detail->Description = 'SO ' . $row->Code . ' ' . $row->Date;
                        }
                    } elseif ($type == 3) {
                        if ($form == 'traveltransaction') {
                            $detail->TravelTransactionDetail = $row->Oid;
                            $detail->Description = 'Travel ' . $row->Code . ' ' . $row->Date;
                        } elseif ($form == 'order') {
                            $detail->PurchaseOrderDetail = $row->Oid;
                            $detail->Description = 'PO ' . $row->Code . ' ' . $row->Date;
                        } else {
                            $detail->PurchaseInvoice = $row->Oid;
                            $detail->Description = 'Invoice ' . $row->Code . ' ' . $row->Date;
                        }
                    }
                    $detail->Currency = $row->Currency;
                    $detail->Rate = $rate;
                    $detail->Account = $row->Account;
                    $detail->Note = $row->Note;
                    $detail->Description = $row->Code . ' ' . $curInvoice->Code;
                    $detail->AmountInvoice = $row->OutstandingAmount;
                    $detail->AmountInvoiceBase = $curInvoice->toBaseAmount($detail->AmountInvoice, $rate);
                    $detail->AmountCashBank = $amountCashBank;
                    $detail->AmountCashBankBase = $amountCashBankBase;
                    $detail->save();
                    $account = Account::where('Oid',$row->Account)->first();
                    $detail->AccountName = isset($account) ? $account->Name : null;
                    $details[] = $detail;
                    
                    unset($row->OutstandingAmount);
                    $row->PaidAmount = $row->TotalAmount;
                    $row->Status = Status::where('Code', 'complete')->first()->Oid;
                    $row->save();
                }
            });
            $this->updateTotal($cashBank);
            //by ser 20200625 sprt nya ga ush soalnya atas udh full kan nilai nya
            // switch ($type) {
            //     case 2: // receipt
            //         $this->linkController->SalesInvoiceCalculateOutstanding($request);
            //         break;
            //     case 3: //payment
            //         if ($form) $this->linkController->TravelTransactionCalculateOutstandingReceipt($request);
            //         else $this->linkController->PurchaseInvoiceCalculateOutstanding($request);
            //         break;
            // }

            return response()->json(
                $details,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function updateTotal(CashBank $data)
    {
        $amount = 0;
        if (isset($data->Details)) foreach ($data->Details as $row) $amount += $row->AmountCashBank;
        $amount = $amount + $data->AdditionalAmount + $data->PrepaidAmount - $data->DiscountAmount + $data->TransferAmount;
        $data->TotalAmount = $amount;
        $data->TotalBase = $data->CurrencyObj->toBaseAmount($amount, $data->Rate);
        $data->TotalAmountWording = convert_number_to_words($data->TotalAmount);
        $data->save();
    }

    public function actionDetail($data)
    {
        $return = [];
        if ($data->PurchaseInvoiceDetailObj) {
            $return[] = [
                'name' => 'Open Purchase Invoice',
                'icon' => 'ListIcon',
                'type' => 'open_view',
                'portalget' => "development/table/vueview?code=PurchaseInvoice",
                'get' => 'purchaseinvoice/'.($data->PurchaseInvoiceDetailObj ? $data->PurchaseInvoiceDetailObj->PurchaseInvoice : null)
            ];
        }
        if ($data->SalesInvoiceDetailObj) {
            $return[] = [
                'name' => 'Open Sales Invoice',
                'icon' => 'ListIcon',
                'type' => 'open_view',
                'portalget' => "development/table/vueview?code=SalesInvoice",
                'get' => 'salesinvoice/'.($data->SalesInvoiceDetailObj ? $data->SalesInvoiceDetailObj->SalesInvoice : null)
            ];
        }
        if ($data->TravelTransactionDetailObj) {
            $return[] = [
                'name' => 'Open Sales Delivery',
                'icon' => 'ListIcon',
                'type' => 'open_view',
                'portalget' => "development/table/vueview?code=TravelTransaction",
                'get' => 'traveltransaction/'.($data->TravelTransactionDetailObj ? $data->TravelTransactionDetailObj->TravelTransaction : null)
            ];
        }
        return $return;
    }

    private function generateRole($data, $role = null, $action = null)
    {
        if ($data instanceof CashBank) $status = $data->StatusObj->Code;
        else $status = Status::entry();
        if (!$role) $role = $this->roleService->list('CashBank');
        if (!$action) $action = $this->roleService->action('CashBank');
        return [
            'IsRead' => $role->IsRead,
            'IsAdd' => $role->IsAdd,
            'IsEdit' => $this->roleService->isAllowDelete($status, $role->IsEdit),
            'IsDelete' => 0, //$this->roleService->isAllowDelete($row->StatusObj, $role->IsDelete),
            'Cancel' => $this->roleService->isAllowCancel($status, $action->Cancel),
            'Entry' => $this->roleService->isAllowEntry($status, $action->Entry),
            'Post' => $this->roleService->isAllowPost($status, $action->Posted),
            'ViewJournal' => $this->roleService->isPosted($status, 1),
            'ViewStock' => $this->roleService->isPosted($status, 1),
            'Print' => $this->roleService->isPosted($status, 1),
        ];
    }
}

// SELECT pi.Oid, pi.TotalAmount, pi.PaidAmount, SUM(acd.AmountInvoice)
//   FROM trdpurchaseinvoice pi
//   LEFT OUTER JOIN acccashbankdetail acd ON acd.PurchaseInvoice = pi.Oid
//   GROUP BY pi.Oid, pi.TotalAmount, pi.PaidAmount;

// UPDATE trdpurchaseinvoice pi
//   LEFT OUTER JOIN (
//     SELECT PurchaseInvoice, SUM(acd.AmountInvoice) AmountInvoice FROM acccashbankdetail acd WHERE PurchaseInvoice IS NOT NULL GROUP BY acd.PurchaseInvoice
//   ) acd ON acd.PurchaseInvoice = pi.Oid
//   SET pi.PaidAmount = acd.AmountInvoice;

// UPDATE trdpurchaseinvoice t LEFT OUTER JOIN sysstatus s ON s.Code = 'complete' set t.Status = s.Oid WHERE t.PaidAmount = t.TotalAmount;