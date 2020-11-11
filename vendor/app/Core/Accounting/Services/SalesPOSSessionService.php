<?php

namespace App\Core\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Accounting\Services\JournalService;
use App\Core\Internal\Entities\Status;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Entities\POSSession;
use App\Core\Accounting\Entities\Account;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\JournalType;
use Illuminate\Support\Facades\Auth;

class SalesPOSSessionService extends JournalObjectService
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

            $possession = POSSession::with([
                'Journals', 'Stocks'
            ])->where('Oid',$id)->first();
            $company = $possession->CompanyObj;
            if($company->IsUsingPOSEnterprise) return null;

            if ($this->isPeriodClosed($possession->Date)) {
                $this->throwPeriodIsClosedError($possession->Date);
            }
            $possession->Journals()->delete();
            $possession->Stocks()->delete();

            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "POSSession" => "p.Oid",
                "Code" => "CONCAT(DATE_FORMAT(NOW(), '%y%m%d'),'-',u.UserName)",                 "Date" => "p.Date", 
            ];
            $fromDetailTable =  "pospointofsaledetail d
                LEFT OUTER JOIN pospointofsale po ON po.Oid = d.PointOfSale
                LEFT OUTER JOIN possession p ON p.Oid = po.POSSession
                LEFT OUTER JOIN user u ON u.Oid = p.User
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.SalesProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'POS'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN sysstatus sp ON sp.Oid = po.Status
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN syspointofsaletype posty ON posty.Oid = po.PointOfSaleType
                LEFT OUTER JOIN mstitem ir ON ir.Oid = i.ItemStockReplacement";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL AND sp.Code='paid' AND posty.Code != 'SRETURN'";
            $dateInitial = Carbon::parse($possession->Date)->format("d/M");
            //endregion

            // //region INSERT STOCK
            $arr = array_merge($arrDefault, [
                "Item" => "CASE WHEN ir.Oid IS NOT NULL THEN ir.Oid ELSE i.Oid END",    
                "Currency" => "co.Currency",            "Rate" => "IFNULL(SUM(IFNULL(po.TotalAmountBase,0)/IFNULL(po.TotalAmount,1)),1)",
                "Quantity" => "SUM(IFNULL(d.Quantity,0))",            "Price" => "SUM(IFNULL(d.Amount,0))", "PriceBase" => "SUM(IFNULL(d.Amount,0) * IFNULL(po.RateAmount,0))",
                "StockQuantity" => "SUM(IFNULL(d.Quantity,0) * -1)",            "StockAmount" => "SUM(IFNULL(d.Amount,0) * IFNULL(po.RateAmount,0))",
                "Warehouse" => "IFNULL(p.Warehouse, co.POSDefaultWarehouse)",
                "Status" => 's.Oid'
            ]);
            $query = "INSERT INTO trdtransactionstock (%s)
                SELECT %s
                FROM {$fromDetailTable} WHERE {$whereClause}  AND i.IsStock = 1 GROUP BY i.Oid";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT STOCK



            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "Status" => "s.Oid", 
                "POSSession" => "p.Oid",             "Source" => "'POSSession'",
                "Code" => "CONCAT(DATE_FORMAT(NOW(), '%y%m%d'),'-',u.UserName)",                 "Date" => "p.Date", 
            ];
            $fromParentTable =  "pospointofsale po
                LEFT OUTER JOIN possession p ON p.Oid = po.POSSession
                LEFT OUTER JOIN user u ON u.Oid = p.User
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'POS'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'                
                LEFT OUTER JOIN sysstatus sp ON sp.Oid = po.Status
                LEFT OUTER JOIN syspointofsaletype posty ON posty.Oid = po.PointOfSaleType
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN accaccount a ON a.Oid = co.SalesDiscountAccount
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstpaymentmethod pm1 ON pm1.Oid = po.PaymentMethod
                LEFT OUTER JOIN mstpaymentmethod pm2 ON pm2.Oid = po.PaymentMethod2
                LEFT OUTER JOIN mstpaymentmethod pm3 ON pm3.Oid = po.PaymentMethod3
                LEFT OUTER JOIN mstpaymentmethod pm4 ON pm4.Oid = po.PaymentMethod4
                LEFT OUTER JOIN mstpaymentmethod pm5 ON pm5.Oid = po.PaymentMethod5
                LEFT OUTER JOIN accaccount pma1 ON pma1.Oid = pm1.Account
                LEFT OUTER JOIN accaccount pma2 ON pma2.Oid = pm2.Account
                LEFT OUTER JOIN accaccount pma3 ON pma3.Oid = pm3.Account
                LEFT OUTER JOIN accaccount pma4 ON pma4.Oid = pm4.Account
                LEFT OUTER JOIN accaccount pma5 ON pma5.Oid = pm5.Account";
            $fromDetailTable =  "pospointofsaledetail d
                LEFT OUTER JOIN pospointofsale po ON po.Oid = d.PointOfSale
                LEFT OUTER JOIN possession p ON p.Oid = po.POSSession
                LEFT OUTER JOIN user u ON u.Oid = p.User
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.SalesProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'POS'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'                
                LEFT OUTER JOIN sysstatus sp ON sp.Oid = po.Status
                LEFT OUTER JOIN syspointofsaletype posty ON posty.Oid = po.PointOfSaleType
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL AND sp.Code='paid' AND posty.Code != 'SRETURN'";
            $dateInitial = Carbon::parse($possession->Date)->format("d/M");
            //endregion

            //region INSERT JOURNAL PENJUALAN
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(u.UserName, ': ' ,DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(po.RateAmount,0), ' x ', FORMAT(SUM((IFNULL(d.Quantity,0) * IFNULL(d.Amount,0)) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0)),2), ')')))", 
                "Account" => "IFNULL(iag.SalesIncome,co.ItemSalesIncome)", 
                "Currency" => "co.Currency",       "Rate" => "IFNULL(SUM(IFNULL(po.TotalAmountBase,0)/IFNULL(po.TotalAmount,1)),1)"
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 0, SUM((IFNULL(d.Quantity,0) * IFNULL(d.Amount,0)) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0)),
                0, SUM(ROUND((IFNULL(d.Quantity,0) * IFNULL(d.Amount,0)) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0) * IFNULL(po.RateAmount,1) , IFNULL(cc.Decimal,0))),
                SUM(ROUND((d.Quantity * d.Amount) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0) * IFNULL(po.RateAmount,1), IFNULL(cc.Decimal,0)))
                FROM {$fromDetailTable} WHERE {$whereClause} 
                AND (d.Quantity * d.Amount) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0) != 0 AND d.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Date,co.Currency, cc.Decimal, iag.SalesInvoice";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PENJUALAN
            //endregion

            $account = "";

            //region INSERT JOURNAL DISKON
            $account = "co.SalesDiscountAccount";
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(u.UserName, ': ', DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(po.RateAmount,0), ' x ', FORMAT(po.DiscountAmount,2), ')')))", 
                "Account" => $account,      
                "Currency" => "co.Currency",            "Rate" => "IFNULL(SUM(po.TotalAmountBase/po.TotalAmount),1)",
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, po.DiscountAmount, 0, 
                ROUND(po.DiscountAmount * IFNULL(po.RateAmount,1), IFNULL(cc.Decimal,0)), 0,
                ROUND(po.DiscountAmount * IFNULL(po.RateAmount,1), IFNULL(cc.Decimal,0)) 
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND po.DiscountAmount != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL DISKON
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(u.UserName, ': ', DATE_FORMAT(p.Date, ' %d/%b '), IF(pma1.Currency = co.Currency, '', CONCAT(' (', FORMAT(po.PaymentRate,0), ' x ', FORMAT(po.PaymentAmount,2), ')')))", 
                "Account" => "pm1.Account",        
                "Currency" => "pma1.Currency",         "Rate" => "IFNULL(po.PaymentRate,1)",
            ]);
            $strField = "DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, po.PaymentAmount, 0, 
                po.PaymentAmountBase, 0, po.PaymentAmountBase
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND po.PaymentAmount != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(u.UserName, ': ', DATE_FORMAT(p.Date, ' %d/%b '), IF(pma2.Currency = co.Currency, '', CONCAT(' (', FORMAT(po.PaymentRate2,0), ' x ', FORMAT(po.PaymentAmount2,2), ')')))", 
                "Account" => "pm2.Account",           
                "Currency" => "pma2.Currency",         "Rate" => "IFNULL(po.PaymentRate2,1)",
            ]);
            $strField = "DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, po.PaymentAmount2, 0, 
                po.PaymentAmountBase2, 0, po.PaymentAmountBase2
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND po.PaymentAmount2 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(u.UserName, ': ', DATE_FORMAT(p.Date, ' %d/%b '), IF(pma3.Currency = co.Currency, '', CONCAT(' (', FORMAT(po.PaymentRate3,0), ' x ', FORMAT(po.PaymentAmount3,2), ')')))", 
                "Account" => "pm3.Account",           
                "Currency" => "pma3.Currency",         "Rate" => "IFNULL(po.PaymentRate3,1)",
            ]);
            $strField = "DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, po.PaymentAmount3, 0, 
                po.PaymentAmountBase3, 0, po.PaymentAmountBase3
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND po.PaymentAmount3 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(u.UserName, ': ',DATE_FORMAT(p.Date, ' %d/%b '), IF(pma4.Currency = co.Currency, '', CONCAT(' (', FORMAT(po.PaymentRate4,0), ' x ', FORMAT(po.PaymentAmount4,2), ')')))", 
                "Account" => "pm4.Account",           
                "Currency" => "pma4.Currency",         "Rate" => "IFNULL(po.PaymentRate4,1)",
            ]);
            $strField = "DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, po.PaymentAmount4, 0, 
                po.PaymentAmountBase4, 0, po.PaymentAmountBase4
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND po.PaymentAmount4 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(u.UserName, ': ',DATE_FORMAT(p.Date, ' %d/%b '), IF(pma5.Currency = co.Currency, '', CONCAT(' (', FORMAT(po.PaymentRate5,0), ' x ', FORMAT(po.PaymentAmount5,2), ')')))", 
                "Account" => "pm5.Account",           
                "Currency" => "pma5.Currency",         "Rate" => "IFNULL(po.PaymentRate5,1)",
            ]);
            $strField = "DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, po.PaymentAmount5, 0, 
                po.PaymentAmountBase5, 0, po.PaymentAmountBase5
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND po.PaymentAmount5 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
        });
    }

    public function unpost($id)
    {
        DB::transaction(function() use ($id) {
            $possession = POSSession::findOrFail($id);
            if ($this->isPeriodClosed($possession->Date)) {
                $this->throwPeriodIsClosedError($possession->Date);
            }
            $possession->Journals()->delete();
            $possession->Stocks()->delete();
        });
    }
}