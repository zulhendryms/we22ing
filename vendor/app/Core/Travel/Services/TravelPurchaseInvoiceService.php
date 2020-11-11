<?php

namespace App\Core\Travel\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Accounting\Services\JournalService;
use App\Core\Internal\Entities\Status;
use App\Core\Travel\Entities\TravelPurchaseInvoice;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\JournalType;
use Illuminate\Support\Facades\Auth;

class TravelPurchaseInvoiceService
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
            $company = $user->CompanyObj;

            $travelPurchaseInvoice = TravelPurchaseInvoice::with([
                'BusinessPartnerObj',
                'AccountObj'
            ])->findOrFail($id);
            $travelPurchaseInvoice->Journals()->delete();

            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "Status" => "s.Oid", 
                "TravelPurchaseInvoice" => "p.Oid", "Source" => "'Purch-Invoice'",
                "Code" => "p.Code",                 "Date" => "p.Date", 
            ];
            $fromParentTable =  "trvpurchaseinvoice p
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'PINV'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN accaccount a ON a.Oid = p.DiscountAccount
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup";
            $fromDetailTable =  "trvsalestransactiondetail d
                LEFT OUTER JOIN trvpurchaseinvoice p ON p.Oid = d.TravelPurchaseInvoice
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'PINV'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN msttax t ON t.Oid = d.PurchaseTax
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN accaccount a ON a.Oid = t.PurchaseAccount
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL";
            $dateInitial = Carbon::parse($travelPurchaseInvoice->Date)->format("d/M");
            //endregion

            //region INSERT JOURNAL HUTANG BELUM DITAGIH
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(d.PurchaseCurrency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(SUM(d.PurchaseSubtotal),2), ')')))", 
                "Account" => "bpag.PurchaseDelivery",       "BusinessPartner" => "p.BusinessPartner", 
                "Currency" => "d.PurchaseCurrency",         "Rate" => "IFNULL(p.Rate,1)",
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, SUM(d.PurchaseSubtotal), 0,
                SUM(ROUND(d.PurchaseSubtotal * IFNULL(d.PurchaseRate,1), IFNULL(cc.Decimal,0))), 0,
                SUM(ROUND(d.PurchaseSubtotal * IFNULL(d.PurchaseRate,1), IFNULL(cc.Decimal,0)))
                FROM {$fromDetailTable} WHERE {$whereClause} 
                AND d.PurchaseSubtotal != 0 AND d.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, bpag.PurchaseDelivery, 
                p.Code, p.Date, d.PurchaseCurrency, co.Currency, cc.Decimal, bp.Name, p.Rate";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL HUTANG BELUM DITAGIH
            //endregion

            //region INSERT JOURNAL PAJAK
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(d.PurchaseCurrency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(SUM(d.PurchaseTaxAmount),2), ')')))", 
                "Account" => "t.PurchaseAccount",           "BusinessPartner" => "NULL", 
                "Currency" => "d.PurchaseCurrency",         "Rate" => "IFNULL(p.Rate,1)",
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, SUM(d.PurchaseTaxAmount), 0,
                SUM(ROUND(d.PurchaseTaxAmount * IFNULL(d.PurchaseRate,1), IFNULL(cc.Decimal,0))), 0,
                SUM(ROUND(d.PurchaseTaxAmount * IFNULL(d.PurchaseRate,1), IFNULL(cc.Decimal,0)))
                FROM {$fromDetailTable} WHERE {$whereClause} 
                AND PurchaseTaxAmount != 0 
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, t.PurchaseAccount, 
                p.Code, p.Date, d.PurchaseCurrency, co.Currency, cc.Decimal, bp.Name, p.Rate";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAJAK
            //endregion

            //region INSERT JOURNAL ADDITIONAL            
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(p.AdditionalAmount,2), ')')))", 
                "Account" => "p.AdditionalAccount",    "BusinessPartner" => "NULL", 
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
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(p.DiscountAmount,2), ')')))", 
                "Account" => "p.DiscountAccount",      "BusinessPartner" => "NULL", 
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

            //region INSERT JOURNAL HUTANG
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(p.TotalAmount,2), ')')))", 
                "Account" => "p.Account",           "BusinessPartner" => "p.BusinessPartner", 
                "Currency" => "p.Currency",         "Rate" => "IFNULL(p.Rate,1)",
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 0, p.TotalAmount, 0, 
                ROUND(p.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)),
                ROUND(p.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0))
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.TotalAmount != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL HUTANG
            //endregion

            //region INSERT JOURNAL SELISH KURS
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': {$company->BusinessPartnerAmountDifferenceObj->Name}', DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (KURS ', FORMAT(p.Rate,0), ')')))", 
                "Account" => "'{$company->BusinessPartnerAmountDifference}'", 
                "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",                   "Rate" => "IFNULL(p.Rate,1)",
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, DebetBase, CreditAmount, CreditBase, TotalBase)
                SELECT %s,
                IF(p.BaseDifference > 0 , p.BaseDifference, 0), IF(p.BaseDifference > 0 , p.BaseDifference, 0),
                IF(p.BaseDifference < 0 , p.BaseDifference * -1, 0), IF(p.BaseDifference < 0 , p.BaseDifference * -1, 0),
                IF(p.BaseDifference < 0, p.BaseDifference * -1, p.BaseDifference)                
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.TotalAmount != 0 AND p.BaseDifference != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL SELISH KURS
            //endregion

            TravelPurchaseInvoice::where('Oid', $id)
            ->update([
                'Status' => Status::posted()->value('Oid'),
            ]);
        });
    }

    public function unpost($id)
    {
        DB::transaction(function() use ($id) {
            $travelPurchaseInvoice = TravelPurchaseInvoice::findOrFail($id);
            $travelPurchaseInvoice->Journals()->delete();
            TravelPurchaseInvoice::where('Oid', $id)
            ->update([
                'Status' => Status::entry()->value('Oid'),
            ]);
        });
    }
}