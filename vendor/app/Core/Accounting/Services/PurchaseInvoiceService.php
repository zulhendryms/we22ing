<?php

namespace App\Core\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Accounting\Services\JournalService;
use App\Core\Internal\Entities\Status;
use App\Core\Trading\Entities\PurchaseInvoice;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\Account;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\JournalType;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoiceService extends JournalObjectService
{
    /** @var JournalService $this->journalService */
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function post($id)
    {
        DB::transaction(function() use ($id) {
            $user = Auth::user();
            $apInvoice = PurchaseInvoice::with([
                'BusinessPartnerObj',
                'AccountObj'
            ])->where('Oid',$id)->first();
            $company = $apInvoice->CompanyObj;

            if ($this->isPeriodClosed($apInvoice->Date ?: now())) {
                $this->throwPeriodIsClosedError($apInvoice->Date ?: now());
            }
            
            $apInvoice->Journals()->delete();
            $apInvoice->Stocks()->delete();

            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "PurchaseInvoice" => "p.Oid",
                "Code" => "p.Code",                 "Date" => "p.Date", 
                "Note" => "p.Note",
            ];
            $fromDetailTable =  "trdpurchaseinvoicedetail d
                LEFT OUTER JOIN trdpurchaseinvoice p ON p.Oid = d.PurchaseInvoice
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.PurchaseProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'PINV'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup
                LEFT OUTER JOIN mstitem ir ON ir.Oid = i.ItemStockReplacement";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL";
            $dateInitial = Carbon::parse($apInvoice->Date)->format("d/M");
            //endregion

            // //region INSERT STOCK
            $account = "p.AdditionalAccount";
            $arr = array_merge($arrDefault, [
                "Item" => "CASE WHEN ir.Oid IS NOT NULL THEN ir.Oid ELSE i.Oid END",    "BusinessPartner" => "p.BusinessPartner", 
                "Currency" => "p.Currency",            "Rate" => "IFNULL(p.Rate,1)",
                "Quantity" => "d.Quantity",            "Price" => "IFNULL(d.Price,0)", "PriceBase" => "IFNULL(d.Price * p.Rate,0)",
                "StockQuantity" => "d.Quantity",            "StockAmount" => "IFNULL(d.Price * p.Rate,0)",
                "Warehouse" => "IFNULL(p.Warehouse, co.POSDefaultWarehouse)",
                "Status" => 's.Oid'
            ]);
            $query = "INSERT INTO trdtransactionstock (%s)
                SELECT %s
                FROM {$fromDetailTable} WHERE {$whereClause} AND i.IsStock = 1 ";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            logger($query);
            DB::insert($query); //INSERT STOCK

            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "Status" => "s.Oid", 
                "PurchaseInvoice" => "p.Oid",             "Source" => "'Purch-Invoice'",
                "Code" => "p.Code",                 "Date" => "p.Date", 
            ];
            $fromParentTable =  "trdpurchaseinvoice p
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'PINV'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN accaccount a ON a.Oid = p.DiscountAccount
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup";
            // $fromDetailTable =  "trvtransactiondetail d
            //     LEFT OUTER JOIN trdpurchaseinvoice p ON p.Oid = d.PurchaseInvoice
            //     LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
            //     LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
            //     LEFT OUTER JOIN accaccount a ON a.Oid = iag.PurchaseProduction
            //     LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'PINV'
            //     LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
            //     LEFT OUTER JOIN company co ON co.Oid = d.Company
            //     LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
            //     LEFT OUTER JOIN msttax t ON t.Oid = d.PurchaseTax
            //     LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
            //     LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
            //     LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
            //     LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup";
            $fromDetailTable =  "trdpurchaseinvoicedetail d
                LEFT OUTER JOIN trdpurchaseinvoice p ON p.Oid = d.PurchaseInvoice
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.PurchaseProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'PINV'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL";
            $dateInitial = Carbon::parse($apInvoice->Date)->format("d/M");
            //endregion

            //region INSERT JOURNAL INVOICE DETAIL
            $account = "CASE WHEN i.IsStock THEN IFNULL(iag.StockAccount,co.ItemStock) ELSE IFNULL(iag.PurchaseExpense,co.ItemPurchaseExpense) END";
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(SUM(d.TotalAmount),2), ')')))", 
                "Account" => $account,       "BusinessPartner" => "p.BusinessPartner", 
                "Currency" => "p.Currency",       "Rate" => "IFNULL(p.Rate,1)"
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, SUM(d.TotalAmount), 0,
                SUM(ROUND(d.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0))), 0,
                SUM(ROUND(d.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)))
                FROM {$fromDetailTable} WHERE {$whereClause} 
                AND d.TotalAmount != 0 AND d.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner,
                p.Code, p.Date, p.Currency, co.Currency, cc.Decimal, bp.Name, p.Rate, iag.PurchaseInvoice, 
                i.IsStock,iag.StockAccount,co.ItemStock, iag.PurchaseExpense,co.ItemPurchaseExpense";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL WIP
            //endregion

            $account = "";
            // $isBooking = $apInvoice->Details()->count() > 0;

            // //region INSERT JOURNAL WIP
            // $arr = array_merge($arrDefault, [
            //     "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(d.PurchaseCurrency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(SUM(d.PurchaseSubtotal),2), ')')))", 
            //     "Account" => "iag.PurchaseProduction",       "BusinessPartner" => "p.BusinessPartner", 
            //     "Currency" => "d.PurchaseCurrency",         "Rate" => "IFNULL(p.Rate,1)",
            //     "TravelTransactionReport" => 'd.TravelTransaction',
            // ]);
            // $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
            //     SELECT %s, SUM(d.PurchaseSubtotal), 0,
            //     SUM(ROUND(d.PurchaseSubtotal * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0))), 0,
            //     SUM(ROUND(d.PurchaseSubtotal * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)))
            //     FROM {$fromDetailTable} WHERE {$whereClause} 
            //     AND d.PurchaseSubtotal != 0 AND d.GCRecord IS NULL 
            //     GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner,
            //     p.Code, p.Date, d.PurchaseCurrency, co.Currency, cc.Decimal, bp.Name, p.Rate";
            // $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            // DB::insert($query); //INSERT JOURNAL WIP
            // //endregion

            //region INSERT JOURNAL ADDITIONAL
            // if ($isBooking) $account = "'".$company->ExpenseInProgress."'"; else $account = "p.AdditionalAccount";
            $account = "p.AdditionalAccount";
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(p.AdditionalAmount,2), ')')))", 
                "Account" => $account,    "BusinessPartner" => "NULL", 
                "Currency" => "p.Currency",            "Rate" => "IFNULL(p.Rate,1)",
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, p.AdditionalAmount, 0, 
                ROUND(p.AdditionalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)), 0, 
                ROUND(p.AdditionalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)) 
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.AdditionalAmount != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL ADDITIONAL
            //endregion

            //region INSERT JOURNAL DISKON
            // if ($isBooking) $account = "'".$company->IncomeInProgress."'"; else $account = "p.DiscountAccount";
            $account = "p.DiscountAccount";
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(p.DiscountAmount,2), ')')))", 
                "Account" => $account,      "BusinessPartner" => "NULL", 
                "Currency" => "p.Currency",            "Rate" => "IFNULL(p.Rate,1)",
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 0, p.DiscountAmount, 0, 
                ROUND(p.DiscountAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)),
                ROUND(p.DiscountAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)) 
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.DiscountAmount != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL DISKON
            //endregion

            //region INSERT JOURNAL PREPAID
            $prepaidAmount = 0;
            $prepaidDiffAmount = 0;
            if (isset($apInvoice->CashBankPaymentPrepaid)) {
                $cashBankPrepaid = CashBank::findOrFail($apInvoice->CashBankPaymentPrepaid);
                $prepaidAmount = $apInvoice->PrepaidAmount;
                
                $arr = array_merge($arrDefault, [
                    "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT({$prepaidAmount},2), ')')))", 
                    "Account" => "'".$cashBankPrepaid->PrepaidAccount."'",           "BusinessPartner" => "p.BusinessPartner", 
                    "Currency" => "p.Currency",         "Rate" => "IFNULL(p.Rate,1)",
                ]);
                $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                    SELECT %s, 0, {$prepaidAmount}, 0, 
                    ROUND(({$prepaidAmount}) * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)),
                    ROUND(({$prepaidAmount}) * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0))
                    FROM {$fromParentTable} WHERE {$whereClause} 
                    AND p.TotalAmount != 0";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //INSERT JOURNAL PREPAID
            } else if (isset($apInvoice->AccountPrepaid)) {
                $prepaidAmount = $apInvoice->PrepaidAmount;
                $accountPrepaid = Account::findOrFail($apInvoice->AccountPrepaid);
                if ($apInvoice->PrepaidAmount > $apInvoice->PrepaidCurrencyAmount)
                    $prepaidRate = $apInvoice->PrepaidAmount / $apInvoice->PrepaidCurrencyAmount;
                else
                    $prepaidRate = $apInvoice->PrepaidCurrencyAmount / $apInvoice->PrepaidAmount;
                
                $arr = array_merge($arrDefault, [
                    "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT({$prepaidAmount},2), ')')))", 
                    "Account" => "'".$apInvoice->AccountPrepaid."'",           "BusinessPartner" => "p.BusinessPartner", 
                    "Currency" => "'".$accountPrepaid->Currency."'",         "Rate" => "IFNULL(".$prepaidRate.",1)",
                ]);
                $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                    SELECT %s, 0, {$apInvoice->PrepaidCurrencyAmount}, 0, 
                    ROUND(({$apInvoice->PrepaidAmount}) * IFNULL({$apInvoice->Rate},1), IFNULL(cc.Decimal,0)),
                    ROUND(({$apInvoice->PrepaidAmount}) * IFNULL({$apInvoice->Rate},1), IFNULL(cc.Decimal,0))
                    FROM {$fromParentTable} WHERE {$whereClause} 
                    AND p.TotalAmount != 0";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //INSERT JOURNAL PREPAID
            }
            //endregion

            //region INSERT JOURNAL HUTANG
            if ($apInvoice->TotalAmount - $prepaidAmount != 0) {      
                $arr = array_merge($arrDefault, [
                    "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(p.TotalAmount - {$prepaidAmount},2), ')')))", 
                    "Account" => "p.Account",           "BusinessPartner" => "p.BusinessPartner", 
                    "Currency" => "p.Currency",         "Rate" => "IFNULL(p.Rate,1)",
                ]);
                if ($apInvoice->TotalAmount - $prepaidAmount < 0) {
                    $strField = "CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase";
                    $amount = ($apInvoice->TotalAmount - $prepaidAmount) * -1;
                } else {
                    $strField = "DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase";
                    $amount = ($apInvoice->TotalAmount - $prepaidAmount);
                }
                
                $query = "INSERT INTO accjournal (%s, {$strField})
                    SELECT %s, 0, {$amount}, 0, 
                    ROUND(({$amount}) * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)),
                    ROUND(({$amount}) * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0))
                    FROM {$fromParentTable} WHERE {$whereClause} 
                    AND p.TotalAmount != 0";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //INSERT JOURNAL HUTANG
            }
            //endregion

            if ($apInvoice->TotalAmount == $apInvoice->PaidAmount) $status = Status::where('Code','complete')->first();
            else $status = Status::where('Code','posted')->first();

            PurchaseInvoice::where('Oid', $id)
            ->update([
                'Status' => $status->Oid,
            ]);
        });
    }

    public function unpost($id)
    {
        DB::transaction(function() use ($id) {
            $apInvoice = PurchaseInvoice::findOrFail($id);
            if ($this->isPeriodClosed($apInvoice->Date)) {
                $this->throwPeriodIsClosedError($apInvoice->Date);
            }
            $apInvoice->Journals()->delete();
            $apInvoice->Stocks()->delete();
            PurchaseInvoice::where('Oid', $id)
            ->update([
                'Status' => Status::entry()->value('Oid'),
            ]);
        });
    }

    public function cancelled($id)
    {
        DB::transaction(function() use ($id) {
            $apInvoice = PurchaseInvoice::findOrFail($id);
            if ($this->isPeriodClosed($apInvoice->Date)) {
                $this->throwPeriodIsClosedError($apInvoice->Date);
            }
            $apInvoice->Journals()->delete();
            $apInvoice->Stocks()->delete();
            PurchaseInvoice::where('Oid', $id)
            ->update([
                'Status' => Status::cancelled()->value('Oid'),
            ]);
        });
    }
}