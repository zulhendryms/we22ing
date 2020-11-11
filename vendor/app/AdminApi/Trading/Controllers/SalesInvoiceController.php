<?php

namespace App\AdminApi\Trading\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Trading\Entities\SalesInvoice;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\CashBankDetail;
use App\Core\Travel\Entities\TravelTransaction;
use App\Core\Accounting\Entities\Account;
use App\Core\Master\Entities\BusinessPartnerGroupUser;
use App\Core\Trading\Entities\SalesInvoiceDetail;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Accounting\Services\SalesInvoiceService;
use App\Core\Internal\Services\AutoNumberService;
use App\Core\Internal\Entities\Status;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class SalesInvoiceController extends Controller
{
    protected $salesInvoiceService;
    protected $roleService;
    private $module;
    private $autoNumberService;
    private $crudController;
    public function __construct(
        SalesInvoiceService $salesInvoiceService,
        AutoNumberService $autoNumberService,
        RoleModuleService $roleService
    ) {
        $this->salesInvoiceService = $salesInvoiceService;
        $this->autoNumberService = $autoNumberService;
        $this->roleService = $roleService;
        $this->module = 'trdsalesinvoice';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        try {
            return $this->crudController->config($this->module);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function presearch(Request $request)
    {
        return null;
    }

    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            $data = DB::table($this->module.' as data');

            // filter businesspartnergroupuser
            $businessPartnerGroupUser = BusinessPartnerGroupUser::select('BusinessPartnerGroup')->where('User', $user->Oid)->pluck('BusinessPartnerGroup');
            if ($businessPartnerGroupUser->count() > 0) {
                $data->whereIn('BusinessPartner.BusinessPartnerGroup', $businessPartnerGroupUser);
            }

            //SECURITY FILTER COMPANY
            if ($user->CompanyAccess) {
                $data = $data->leftJoin('company AS CompanySecurity', 'CompanySecurity.Oid', '=', 'data.Company');
                $tmp = json_decode($user->CompanyAccess);
                $data = $data->whereIn('CompanySecurity.Code', $tmp);
            }
            
            $data = $this->crudController->list($this->module, $data, $request);
            $role = $this->roleService->list('SalesInvoice'); //rolepermission
            foreach ($data->data as $row) {
                $tmp = SalesInvoice::findOrFail($row->Oid);
                $row->Action = $this->action($tmp);
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
            $data = DB::table($this->module.' as data');
            $data = $this->crudController->index($this->module, $data, $request, false);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function showSub($Oid)
    {
        $data = $this->crudController->detail($this->module, $Oid);
        $data->Action = $this->action($data);
        foreach ($data->Details as $row) {
            $row->Action = $this->actionDetail($row);
        }
        return $data;
    }

    public function show(SalesInvoice $data)
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
                $data = $this->crudController->saving($this->module, $request, $Oid, false);
                
                //logic
                if (isset($data->PaymentTerm)) {
                    $data->DueDate = addPaymentTermDueDate($data->Date, $data->PaymentTerm);
                }
                foreach ($data->Details as $row) {
                    $row = $this->crudController->saveTotal($row);
                    $row->save();
                }
                foreach ($data->DetailTravels as $row) {
                    $row->TotalAmount = (($row->QtyAdult ?: 0) * ($row->PriceAdult ?: 0)) + (($row->QtyCWB ?: 0) * ($row->PriceCWB ?: 0))
                        + (($row->QtyCNB ?: 0) * ($row->PriceCNB ?: 0)) + (($row->QtyINF ?: 0) * ($row->PriceINF ?: 0)) + (($row->QtyExBed ?: 0) * ($row->PriceExBed ?: 0))
                        + (($row->QtyFOC ?: 0) * ($row->PriceFOC ?: 0)) + (($row->QtyTL ?: 0) * ($row->PriceTL ?: 0));
                    $row->save();
                }                
                // $data = $this->crudController->saveTotal($data);
                $this->calculateTotalAmount($data);
            });
            $role = $this->roleService->list('SalesInvoice'); //rolepermission
            $data = $this->showSub($data->Oid);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function calculateTotalAmount(SalesInvoice $data)
    {

        // salesinvoice->totalamount = salesinvoicedetial->totalamount + salesinvoicedetailtravel->totalamount + parent->additonal - parent->discount
        $totalAmount = 0;
        $totalAmountTravel = 0;
        foreach ($data->Details as $row) {
            $totalAmount += ($row->Quantity ?: 0) * ($row->Price ?: 0);
        }
        foreach ($data->DetailTravels as $row) {
            $totalAmountTravel += (($row->QtyAdult ?: 0) * ($row->PriceAdult ?: 0)) + (($row->QtyCWB ?: 0) * ($row->PriceCWB ?: 0))
                        + (($row->QtyCNB ?: 0) * ($row->PriceCNB ?: 0)) + (($row->QtyINF ?: 0) * ($row->PriceINF ?: 0)) + (($row->QtyExBed ?: 0) * ($row->PriceExBed ?: 0))
                        + (($row->QtyFOC ?: 0) * ($row->PriceFOC ?: 0)) + (($row->QtyTL ?: 0) * ($row->PriceTL ?: 0));
        }
        $data->SubtotalAmount = $totalAmount + $totalAmountTravel;
        $data->TotalAmount = $data->SubtotalAmount + $data->AdditionalAmount - $data->DiscountAmount;

        if ($data->PointOfSale) {
            $amount = DB::select("SELECT SUM(TotalAmount) Amount FROM trdsalesinvoice t WHERE t.PointOfSale = '{$data->PointOfSale}'");
            if ($amount) $amount = $amount[0]->Amount;
            $tmp = TravelTransaction::where('Oid',$data->PointOfSale)->first();
            $tmp->AmountTourFareTotal = $data->TotalAmount;
            $tmp->save();
        }

        $data->save();
    }

    public function destroy(SalesInvoice $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function post(SalesInvoice $data)
    {
        try {
            $this->salesInvoiceService->post($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function unpost(SalesInvoice $data)
    {
        try {
            $this->salesInvoiceService->unpost($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function cancelled(SalesInvoice $data)
    {
        try {
            $this->salesInvoiceService->cancelled($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function action(SalesInvoice $data)
    {
        $url = 'salesinvoice';
        $actionEntry = [
            'name' => 'Change to ENTRY',
            'icon' => 'UnlockIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/unpost',
        ];
        $actionPosted = [
            'name' => 'Change to POSTED',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/post',
        ];
        $actionconvertToReceipt = [
            'name' => 'Convert To Receipt',
            'icon' => 'ZapIcon',
            'type' => 'global_form',
            'post' => $url . '/{Oid}/convert',
            'showModal' => false,
            'afterRequest' => "init",
            'form' => [
                [
                    'fieldToSave' => "Account",
                    'hiddenField' => "AccountName",
                    'type' => "autocomplete",
                    'column' => "1/2",
                    'default' => null,
                    'store' => "autocomplete/account",
                    'source' => [],
                    "onChange" => [
                        'action' => "request",
                        "get" => "/currency/rate?currency={Currency}&date={Date}",
                        "link" => "Rate",
                    ],
                    'params' => [
                        "form" => "cashbank",
                        "term" => "",
                        "type" => "combo",
                    ]
                ],
                [
                    'fieldToSave' => "Currency",
                    "disabled" => true,
                    "hiddenField" => "CurrencyName",
                    "source" => "currency",
                    "type" => "combobox",
                ],
                [
                    'fieldToSave' => "Rate",
                    'type' => "inputtext",
                    'default' => 1,
                ],
                [
                    'fieldToSave' => "Amount",
                    'overrideLabel' => "Amount to pay",
                    'type' => "inputarea",
                    'default' => null,
                ],
            ]
        ];
        $actionCancelled = [
            'name' => 'Change to Cancelled',
            'icon' => 'XIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/cancelled',
        ];
        $actionPrintprereportsi = [
            'name' => 'Print (Standard)',
            'icon' => 'PrinterIcon',
            'type' => 'open_report',
            'hide' => true,
            'get' => 'prereport/salesinvoice?oid={Oid}&report=salesinvoice',
        ];
        $actionPrintprereportInvoiceBilling = [
            'name' => 'Print Invoice Billing',
            'icon' => 'PrinterIcon',
            'type' => 'open_report',
            'hide' => true,
            'get' => 'prereport/salesinvoice?oid={Oid}&report=invoicebilling',
        ];
        $actionPrintprereportInvoiceBillingTravel = [
            'name' => 'Print Invoice Billing Travel',
            'icon' => 'PrinterIcon',
            'type' => 'open_report',
            'hide' => true,
            'get' => 'prereport/salesinvoice?oid={Oid}&report=invoicebillingtravel',
        ];
        $actionPrinthalfcontinous = [
            'name' => 'Print (half continuous)',
            'icon' => 'PrinterIcon',
            'type' => 'open_report',
            'hide' => true,
            'get' => 'report/faktursalesinvoice/{Oid}',
        ];
        $actionPrintAceTours = [
            'name' => 'Print Invoice Report',
            'icon' => 'PrinterIcon',
            'type' => 'open_report',
            'hide' => true,
            'get' => 'prereport/invoice?oid={Oid}&report=acetoursinvoice',
        ];
        $actionViewJournal = [
            'name' => 'View Journal',
            'icon' => 'BookOpenIcon',
            'type' => 'open_grid',
            'get' => 'journal?' . $url . '={Oid}',
        ];
        $actionViewStock = [
            'name' => 'View Stock',
            'icon' => 'PackageIcon',
            'type' => 'open_grid',
            'get' => 'stock?' . $url . '={Oid}',
        ];
        $actionOpen = [
            "name" => "Edit",
            "icon" => "PackageIcon",
            "type" => "edit",
        ];
        $actionDelete = [
            'name' => 'Delete',
            'icon' => 'TrashIcon',
            'type' => 'confirm',
            'delete' => $url . '/{Oid}'
        ];
        $openCashBank = [
            'name' => 'Open CashBank',
            'icon' => 'ListIcon',
            'type' => 'open_grid',
            'get' => 'salesinvoice/relatedcashbank/{Oid}'
        ];
        $return = [];
        // switch ($data->StatusObj->Code) {
        switch ($data->Status ? $data->StatusObj->Code : "entry") {
            case "":
                $return[] = $actionOpen;
                $return[] = $actionPosted;
                $return[] = $actionDelete;
                $return[] = $actionCancelled;
                break;
            case "posted":
                $return[] = $actionEntry;
                $return[] = $actionconvertToReceipt;
                $return[] = $actionPrintprereportsi;
                $return[] = $actionPrintprereportInvoiceBilling;
                $return[] = $actionPrintprereportInvoiceBillingTravel;
                $return[] = $actionPrinthalfcontinous;
                $return[] = $actionPrintAceTours;
                $return[] = $actionViewJournal;
                $return[] = $actionViewStock;
                $return[] = $openCashBank;
                break;
            case "entry":
                $return[] = $actionOpen;
                $return[] = $actionPosted;
                $return[] = $actionCancelled;
                $return[] = $actionDelete;
                $return[] = $actionPrintprereportInvoiceBilling;
                break;
        }
        $return = actionCheckCompany($this->module, $return);
        return $return;
    }

    public function relatedCashBank(SalesInvoice $data) {
        $tmp = SalesInvoiceDetail::where('SalesInvoice', $data)->pluck('Oid');
        $tmp = CashBankDetail::whereNotNull('GCRecord')
            ->whereIn('SalesInvoiceDetail', $tmp)->pluck('CashBank');
        $data = CashBank::whereIn('Oid',$tmp)->get();
        $results = [];
        foreach($data as $row) {
            $result[] = [
                'Oid' => $data->Oid,
                'Code' => $data->Code,
                'Date' => $data->Date,
                'BusinessPartner' => $data->BusinessPartnerObj ? $data->BusinessPartnerObj->Name : null,
                'Status' => $data->StatusObj ? $data->StatusObj->Code : null,
                'Action' => [
                    'name' => 'Open CashBank',
                    'icon' => 'ListIcon',
                    'type' => 'open_view',
                    'portalget' => "development/table/vueview?code=CashBank",
                    'get' => 'cashbank/{Oid}'    
                ]
            ];
        }
        return $results;
    }

    public function convertToReceipt(SalesInvoice $data, Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF
        $cashbank = new CashBank();
        try {
            DB::transaction(function () use ($request, $cashbank, &$data) {
                $cashbank->Type = 2; //receipt
                $account = Account::with('CurrencyObj')->findOrFail($request->Account);
                $cur = $account->CurrencyObj;
                $cashbank->Company = $data->Company;
                $cashbank->Code = '<<Auto>>';
                $cashbank->Date = Carbon::now();
                $cashbank->Account = $account->Oid;
                $cashbank->Currency = $account->Currency;
                $cashbank->BusinessPartner = $data->BusinessPartner;
                $cashbank->Note = $data->Note;
                $cashbank->Rate = $request->Rate ?: $account->CurrencyObj->getRate($cashbank->Date)->MidRate;
                $cashbank->Status = Status::entry()->first()->Oid;
                $cashbank->save();
                $cashbank->Code = $this->autoNumberService->generate($cashbank, 'acccashbank');
                $cashbank->save();

                $details = [];
                if ($cashbank->Currency == $data->Currency) {
                    $amountCashBank = $data->TotalAmount;
                    $amountCashBankBase = $cur->toBaseAmount($data->TotalAmount, $data->Rate);
                } else {
                    $amountCashBank = $request->Amount;
                    $amountCashBankBase = $cur->toBaseAmount($request->Amount, $data->Rate);
                }
                $details[] = new CashBankDetail([
                    'SalesInvoice' => $data->Oid,
                    'Account' => $data->Account,
                    'Currency' => $data->Currency,
                    'Rate' => $data->Rate,
                    'AmountInvoice' => $data->TotalAmount,
                    'AmountInvoiceBase' => $cur->toBaseAmount($data->TotalAmount, $data->Rate),
                    'AmountCashBank' => $amountCashBank,
                    'AmountCashBankBase' => $amountCashBankBase,
                    'Note' => $data->Note,
                    'CostCenter' => $data->CostCenter,
                ]);
                $cashbank->Details()->saveMany($details);

                if (!$cashbank) {
                    throw new \Exception('Data is failed to be saved');
                }

                $strOid = '';
                foreach ($cashbank->Details as $rowdb) {
                    $strOid = $strOid . ($strOid ? ", " : "") . "'" . $rowdb->SalesInvoice . "'";
                }
                $query = "UPDATE trdsalesinvoice sinv
                    LEFT OUTER JOIN (
                        SELECT cbd.SalesInvoice, SUM(IFNULL(cbd.AmountInvoice,0)) AS PaidAmount 
                        FROM acccashbankdetail cbd 
                        WHERE cbd.SalesInvoice IN (" . $strOid . ") 
                        AND cbd.GCRecord IS NULL GROUP BY cbd.SalesInvoice
                    ) cbd ON cbd.SalesInvoice = sinv.Oid
                    SET sinv.PaidAmount = IFNULL(cbd.PaidAmount,0)
                    WHERE sinv.Oid IN (" . $strOid . ")";
                if ($strOid != '') {
                    DB::Update($query);
                }
            });

            return response()->json(
                $cashbank,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function partialSalesOrder(Request $request)
    {
        $query = "SELECT so.*, CONCAT(i.Name,' - ',i.Code) AS Name,
                    (IFNULL(so.TotalAmount,0) - IFNULL(so.ReceiptAmount,0)) AS OutstandingAmount, c.Code AS CurrencyCode
                    FROM trdsalesorder so
                    LEFT OUTER JOIN trdsalesorderdetail sod ON so.Oid = sod.SalesOrder
                    LEFT OUTER JOIN mstitem i ON sod.Item = i.Oid
                    LEFT OUTER JOIN sysstatus s ON so.Status = s.Oid
                    LEFT OUTER JOIN mstcurrency c ON c.Oid = so.Currency
                    WHERE (IFNULL(so.TotalAmount,0) - IFNULL(so.ReceiptAmount,0)) > 0
                    AND so.GCRecord IS NULL 
                    AND so.Oid NOT IN ({$request->input('exception')})
                    AND so.BusinessPartner = '{$request->input('businesspartner')}'
                    AND DATE_FORMAT(so.Date, '%Y-%m-%d') <= '{$request->input('date')}'
                    AND s.Code = 'posted'";
        $data = DB::select($query);


        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function partialSalesOrderAdd(Request $request)
    {
        try {
            $details = [];
            DB::transaction(function () use ($request, &$details) {
                $salesInvoice = SalesInvoice::findOrFail($request->input('oid'));
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
                $string = "";
                foreach ($request as $row) {
                    $string = ($string ? $string . "," : null) . "'" . $row . "'";
                }
                $query = "SELECT sod.*, (IFNULL(sod.Quantity,0) - IFNULL(sod.QuantityInvoiced,0)) AS OutstandingQuantity,
                            i.Name AS ItemName, so.Code AS ParentCode
                            FROM trdsalesorder so
                            LEFT OUTER JOIN trdsalesorderdetail sod ON sod.SalesOrder = so.Oid
                            LEFT OUTER JOIN sysstatus s ON so.Status = s.Oid
                            LEFT OUTER JOIN mstitem i ON i.Oid = sod.Item
                            WHERE (IFNULL(so.TotalAmount,0) - IFNULL(so.ReceiptAmount,0)) > 0
                            AND so.GCRecord IS NULL AND sod.Oid IN (" . $string . ")
                            ";
                $data = DB::select($query);
                $total = 0;
                $sequence = (SalesInvoiceDetail::where('SalesInvoice', $data->Oid)->max('Sequence') ?: 0) + 1;
                foreach ($data as $row) {
                    $detail = new SalesInvoiceDetail();
                    $detail->SalesInvoice = $salesInvoice->Oid;
                    $detail->SalesOrderDetail = $row->Oid;
                    $detail->Company = $row->Company;
                    $detail->Sequence = $sequence;
                    $detail->Reference = 'SO: ' . $row->ParentCode;
                    $sequence = $sequence;
                    $detail->Item = $row->Item;
                    $detail->Quantity = $row->OutstandingQuantity;
                    $detail->QuantityBase = $row->OutstandingQuantity;
                    $detail->ItemUnit = $row->ItemUnit;
                    $detail->Price = $row->Price;
                    $detail->DiscountAmount = $row->DiscountAmount;
                    $detail->DiscountPercentage = $row->DiscountPercentage;
                    $detail->SubtotalAmount = $row->OutstandingQuantity * $row->Price;
                    $detail->TotalAmount = $detail->SubtotalAmount - $row->DiscountAmount;
                    $detail->Note = $row->Note;
                    $detail->CostCenter = $row->CostCenter;
                    $detail->save();

                    $detail->ItemName = $row->ItemName;

                    $tmp = SalesInvoiceDetail::findOrFail($row->Oid);
                    $tmp->QuantityInvoiced = $tmp->Quantity;
                    $tmp->save();
                    $sequence++;
                    $total += $detail->TotalAmount;

                    $details[] = $detail;
                }
                $this->linkController->PurchaseOrderCalculateOutstanding($request);
                $this->crudController->saveTotal($purchaseInvoice);
            });

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

    public function actionDetail($data)
    {
        $return = [];
        if ($data->SalesOrderDetailObj) {
            $return[] = [
                'name' => 'Open Sales Order',
                'icon' => 'ListIcon',
                'type' => 'open_view',
                'portalget' => "development/table/vueview?code=SalesOrder",
                'get' => 'salesorder/'.($data->SalesOrderDetailObj ? $data->SalesOrderDetailObj->SalesOrder : null)
            ];
        }
        if ($data->SalesDeliveryDetailObj) {
            $return[] = [
                'name' => 'Open Sales Delivery',
                'icon' => 'ListIcon',
                'type' => 'open_view',
                'portalget' => "development/table/vueview?code=SalesDelivery",
                'get' => 'salesdelivery/'.($data->SalesDeliveryDetailObj ? $data->SalesDeliveryDetailObj->SalesDelivery : null)
            ];
        }
        return $return;
    }

    private function generateRole($data, $role = null, $action = null)
    {
        if ($data instanceof SalesInvoice) {
            $status = $data->StatusObj->Code;
        } else {
            $status = Status::entry();
        }
        if (!$role) {
            $role = $this->roleService->list('SalesInvoice');
        }
        if (!$action) {
            $action = $this->roleService->action('SalesInvoice');
        }
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
