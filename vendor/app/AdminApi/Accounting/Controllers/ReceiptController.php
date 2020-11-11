<?php

namespace App\AdminApi\Accounting\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Resources\CashBankResource;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\CashBankDetail;
use App\Core\Accounting\Entities\Account;
use App\Core\Internal\Entities\Status;
use App\Core\Master\Entities\Currency;
use App\Core\Security\Services\RoleModuleService;
use Validator;

class ReceiptController extends Controller
{
    protected $roleService;
    
    public function __construct(
        RoleModuleService $roleService  
        )
    {
        $this->roleService = $roleService;
    }
    public function save(Request $request, $Oid = null)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        $dataArray = object_to_array($request);
        
        $messsages = array(
            'Code.required'=>__('_.Code').__('error.required'),
            'Code.max'=>__('_.Code').__('error.max'),
            'Date.required'=>__('_.Date').__('error.required'),
            'Date.date'=>__('_.Date').__('error.date'),
            'Rate.required'=>__('_.Rate').__('error.required'),
            'Rate.max'=>__('_.Rate').__('error.max'),
            'Status.required'=>__('_.Status').__('error.required'),
            'Status.exists'=>__('_.Status').__('error.exists'),
            'Account.required'=>__('_.Account').__('error.required'),
            'Account.exists'=>__('_.Account').__('error.exists'),
            'BusinessPartner.required'=>__('_.BusinessPartner').__('error.required'),
            'BusinessPartner.exists'=>__('_.BusinessPartner').__('error.exists'),
        );
        $rules = array(
            'Code' => 'required|max:255',
            'Date' => 'required|date',
            'Rate' => 'required|max:255',
            'Status' => 'required|exists:sysstatus,Oid',
            'Account' => 'required|exists:accaccount,Oid',
            'BusinessPartner' => 'required|exists:mstbusinesspartner,Oid',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        // return response()->json(
        //     $request, Response::HTTP_OK
        // );
        // if ($request->AdditionalAmount < 0 ) throw new \Exception("Additional amount cannot below 0");
        // if ($request->DiscountAmount < 0 ) throw new \Exception("Discount amount cannot below 0");
        // if ($request->PrepaidAmount < 0 ) throw new \Exception("Prepaid amount cannot below 0");
        // if ($request->RateAmount < 0 ) throw new \Exception("Rate amount cannot below 0");
        // if ($request->TransferAmount < 0 ) throw new \Exception("Transfer amount cannot below 0");        
        // checkperiod
        try {            
            DB::transaction(function () use ($request, $Oid, &$data) {
            if (!$Oid) $data = new CashBank();
            else $data = CashBank::findOrFail($Oid);

            $data->Type = 2; //receipt
            $account = Account::with('CurrencyObj')->findOrFail($request->Account);
            $cur = $account->CurrencyObj;
            // $data->Company = Auth::user()->Company;
            $data->Code = $request->Code == '<<Auto>>' ? now()->format('ymdHis').'-'.str_random(3) : $request->Code;
            $data->Date = $request->Date;
            $data->Account = $account->Oid;
            $data->Currency = $account->Currency;
            $data->BusinessPartner = $request->BusinessPartner;
            $data->AdditionalAccount = $request->AdditionalAccount;
            $data->AdditionalAmount = $request->AdditionalAmount ?: 0;
            $data->DiscountAccount = $request->DiscountAccount;
            $data->DiscountAmount = $request->DiscountAmount ?: 0;
            $data->PrepaidAccount = $request->PrepaidAccount;
            $data->PrepaidAmount = $request->PrepaidAmount ?: 0;
            $data->Note = $request->Note;
            $data->Rate = $request->Rate ?: $account->CurrencyObj->getRate($data->Date)->MidRate;
            $data->Status = $request->Status ?: Status::entry()->first()->Oid;
            $data->save();

            if ($data->Details()->count() != 0) {
                foreach ($data->Details as $rowdb) {
                    $found = false;               
                    foreach ($request->Details as $rowapi) {
                        if (isset($rowapi->Oid)) {
                            if ($rowdb->Oid == $rowapi->Oid) $found = true;
                        }
                    }
                    if (!$found) {
                        $detail = CashBankDetail::findOrFail($rowdb->Oid);
                        $detail->delete();
                    }
                }
            }

            $totalAmount = 0;
            if($request->Details) {
                $details = [];
                foreach ($request->Details as $row) {
                    if ($request->Currency == $row->Currency) {
                        $amountCashBank = $row->AmountInvoice;
                        $amountCashBankBase = $cur->toBaseAmount($row->AmountInvoice, $row->Rate);
                    } else{
                        $amountCashBank = $row->AmountCashBank;
                        $amountCashBankBase = $cur->toBaseAmount($row->AmountCashBank, $data->Rate);
                    } 
                    if (isset($row->Oid)) {
                        $detail = CashBankDetail::findOrFail($row->Oid);
                        $detail->Description = $row->Description;
                        // $detail->SalesInvoice = $row->SalesInvoice;
                        $detail->Company = $data->Company;
                        $detail->CashBank = $data->Oid;
                        $detail->Account = $row->Account;
                        $detail->Currency = $row->Currency;
                        $detail->Rate = $row->Rate; //apabila ambil rate lama juga salah karena mgkn aja udh bbrp bln udh beda, ambil rate parent jga sala, shrsnya ambil rate terbaru & akhir bln ulang hitung
                        $detail->AmountInvoice = $row->AmountInvoice;
                        $detail->AmountInvoiceBase = $cur->toBaseAmount($row->AmountInvoice, $row->Rate);
                        $detail->AmountCashBank = $amountCashBank;
                        $detail->AmountCashBankBase = $amountCashBankBase;
                        $detail->save();
                    } else {
                        $details[] = new CashBankDetail([
                            'Company' => $data->Company,
                            'CashBank' => $data->Oid,
                            'Description' => $row->Description,
                            'SalesInvoice' => $row->SalesInvoice,
                            'Account' => $row->Account,
                            'Currency' => $row->Currency,
                            'Rate' => $row->Rate,
                            'AmountInvoice' => $row->AmountInvoice,
                            'AmountInvoiceBase' => $cur->toBaseAmount($row->AmountInvoice, $row->Rate),
                            'AmountCashBank' => $amountCashBank,
                            'AmountCashBankBase' => $amountCashBankBase,
                        ]);
                    }
                    $totalAmount += $amountCashBank;
                }
                $data->Details()->saveMany($details);
            }
            $data->TotalAmount = $totalAmount - $data->DiscountAmount + $data->AdditionalAmount + $data->PrepaidAmount;
            $data->TotalBase = $cur->toBaseAmount($data->TotalAmount, $data->Rate);
                $data->save();
                $data->load('Details');
                $data->fresh();
            });            
                
            $role = $this->roleService->list('CashBank');
            $action = $this->roleService->action('CashBank');
            $data->BusinessPartnerName = $data->BusinessPartner ? $data->BusinessPartnerObj->Name : null;
            $data->CurrencyName = $data->Currency ? $data->CurrencyObj->Code : null;
            $data->StatusName = $data->Status ? $data->StatusObj->Name : null;
            $data->Role = [
                'IsRead' => $role->IsRead,
                'IsAdd' => $role->IsAdd,
                'IsEdit' => $this->roleService->isAllowDelete($data->StatusObj, $role->IsEdit),
                'IsDelete' => 0, //$this->roleService->isAllowDelete($row->StatusObj, $role->IsDelete),
                'Cancel' => $this->roleService->isAllowCancel($data->StatusObj, $action->Cancel),
                'Entry' => $this->roleService->isAllowEntry($data->StatusObj, $action->Entry),
                'Post' => $this->roleService->isAllowPost($data->StatusObj, $action->Posted),
                'ViewJournal' => $this->roleService->isPosted($data->StatusObj, 1),
            ];

            $strOid = '';
            foreach ($data->Details as $rowdb) {
                $strOid = $strOid.($strOid ? ", " : "")."'".$rowdb->SalesInvoice."'";
            }
            $query = "UPDATE trdsalesinvoice sinv
                LEFT OUTER JOIN (
                    SELECT cbd.SalesInvoice, SUM(IFNULL(cbd.AmountInvoice,0)) AS PaidAmount 
                    FROM acccashbankdetail cbd 
                    WHERE cbd.SalesInvoice IN (".$strOid.") 
                    AND cbd.GCRecord IS NULL GROUP BY cbd.SalesInvoice
                ) cbd ON cbd.SalesInvoice = sinv.Oid
                SET sinv.PaidAmount = IFNULL(cbd.PaidAmount,0)
                WHERE sinv.Oid IN (".$strOid.")";
            if ($strOid != '') DB::Update($query);

            // $data = new CashBankResource($data);
            return response()->json(
                $data, Response::HTTP_CREATED
            )->header('Location', route('AdminApi\CashBank::show', ['data' => $data->Oid]));
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
    public function invoice(Request $request)
    {
        try {            
            $query = "SELECT  sl.Oid, 
                CONCAT(sl.Code, '  (', DATE_FORMAT(sl.Date, '%Y-%m-%d'),'):  ', c.Code,' ',(IFNULL(sl.TotalAmount,0) - IFNULL(sl.PrepaidAmount,0) - IFNULL(sl.PaidAmount,0))) AS Name, 
                c.Oid AS Currency, (IFNULL(sl.TotalAmount,0) - IFNULL(sl.PrepaidAmount,0) - IFNULL(sl.PaidAmount,0)) AS AmountInvoice, sl.Rate, sl.Account
                FROM trdsalesinvoice sl
                LEFT OUTER JOIN mstbusinesspartner bp ON sl.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstcurrency c ON sl.Currency = c.Oid
                LEFT OUTER JOIN sysstatus s ON sl.Status = s.Oid
                WHERE (IFNULL(sl.TotalAmount,0) - IFNULL(sl.PrepaidAmount,0) - IFNULL(sl.PaidAmount,0)) > 0
                AND sl.GCRecord IS NULL
                AND sl.Oid NOT IN ({$request->input('exception')})
                AND sl.BusinessPartner = '{$request->input('businesspartner')}'
                AND sl.Date <= '{$request->input('date')}'
                AND s.Code = 'posted'";
            $data = DB::select($query);
            foreach($data as $row) {
                $cur = Currency::findOrFail($row->Currency);
                $row->AmountCashBank = $cur->convertRate($request->input('currency'),$row->AmountInvoice);
            }
            return response()->json(
                $data,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
            