<?php

namespace App\Core\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Accounting\Services\JournalService;
use App\Core\Internal\Entities\Status;
use App\Core\POS\Entities\PointOfSale;
use App\Core\Accounting\Entities\Account;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\JournalType;
use Illuminate\Support\Facades\Auth;

class SalesPOSService extends JournalObjectService
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

            $pos = PointOfSale::with([
                'CustomerObj', 'Journals', 'Stocks'
            ])->where('Oid',$id)->first();
            $company = $pos->CompanyObj;
            if(!$company->IsUsingPOSEnterprise) return null;

            if ($this->isPeriodClosed($pos->Date)) {
                $this->throwPeriodIsClosedError($pos->Date);
            }
            $pos->Journals()->delete();
            $pos->Stocks()->delete();


            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "PointOfSale" => "p.Oid", "POSSession" => "p.POSSession",
                "Code" => "p.Code",                 "Date" => "p.Date", 
            ];
            $fromDetailTable =  "pospointofsaledetail d
                LEFT OUTER JOIN pospointofsale p ON p.Oid = d.PointOfSale
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.SalesProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'POS'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.Customer
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup
                LEFT OUTER JOIN mstitem ir ON ir.Oid = i.ItemStockReplacement";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL";
            $dateInitial = Carbon::parse($pos->Date)->format("d/M");
            //endregion

            // //region INSERT STOCK
            $arr = array_merge($arrDefault, [
                "Item" => "CASE WHEN ir.Oid IS NOT NULL THEN ir.Oid ELSE i.Oid END",    "BusinessPartner" => "p.Customer", 
                "Currency" => "p.Currency",            "Rate" => "IFNULL(p.RateAmount,1)",
                "Quantity" => "d.Quantity",            "Price" => "IFNULL(d.Amount,0)", "PriceBase" => "IFNULL(d.Amount * p.RateAmount,0)",
                "StockQuantity" => "d.Quantity * -1",            "StockAmount" => "IFNULL(d.Amount * p.RateAmount,0)",
                "Warehouse" => "IFNULL(p.Warehouse, co.POSDefaultWarehouse)",
                "Status" => 's.Oid'
            ]);
            $query = "INSERT INTO trdtransactionstock (%s)
                SELECT %s
                FROM {$fromDetailTable} WHERE {$whereClause}  AND i.IsStock = 1 ";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT STOCK



            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "Status" => "s.Oid", 
                "PointOfSale" => "p.Oid",             "Source" => "'POS'",
                "Code" => "p.Code",                 "Date" => "p.Date", 
            ];
            $fromParentTable =  "pospointofsale p
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'POS'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN accaccount a ON a.Oid = co.SalesDiscountAccount
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.Customer
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup
                LEFT OUTER JOIN mstpaymentmethod pm1 ON pm1.Oid = p.PaymentMethod
                LEFT OUTER JOIN mstpaymentmethod pm2 ON pm2.Oid = p.PaymentMethod2
                LEFT OUTER JOIN mstpaymentmethod pm3 ON pm3.Oid = p.PaymentMethod3
                LEFT OUTER JOIN mstpaymentmethod pm4 ON pm4.Oid = p.PaymentMethod4
                LEFT OUTER JOIN mstpaymentmethod pm5 ON pm5.Oid = p.PaymentMethod5
                LEFT OUTER JOIN accaccount pma1 ON pma1.Oid = pm1.Account
                LEFT OUTER JOIN accaccount pma2 ON pma2.Oid = pm2.Account
                LEFT OUTER JOIN accaccount pma3 ON pma3.Oid = pm3.Account
                LEFT OUTER JOIN accaccount pma4 ON pma4.Oid = pm4.Account
                LEFT OUTER JOIN accaccount pma5 ON pma5.Oid = pm5.Account";
            $fromDetailTable =  "pospointofsaledetail d
                LEFT OUTER JOIN pospointofsale p ON p.Oid = d.PointOfSale
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.SalesProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'POS'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.Customer
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL";
            $dateInitial = Carbon::parse($pos->Date)->format("d/M");
            //endregion

            //region INSERT JOURNAL PENJUALAN
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.RateAmount,0), ' x ', FORMAT(SUM((d.Quantity * d.Amount) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0)),2), ')')))", 
                "Account" => "IFNULL(iag.SalesIncome,co.ItemSalesIncome)",       "BusinessPartner" => "p.Customer", 
                "Currency" => "p.Currency",       "Rate" => "IFNULL(p.RateAmount,1)"
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 0, SUM((IFNULL(d.Quantity,0) * IFNULL(d.Amount,0)) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0)),
                0, SUM(ROUND((IFNULL(d.Quantity,0) * IFNULL(d.Amount,0)) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0) * IFNULL(p.RateAmount,1), IFNULL(cc.Decimal,0))),
                SUM(ROUND((IFNULL(d.Quantity,0) * IFNULL(d.Amount,0)) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0) * IFNULL(p.RateAmount,1), IFNULL(cc.Decimal,0)))
                FROM {$fromDetailTable} WHERE {$whereClause} 
                AND (d.Quantity * d.Amount) - d.DiscountAmount - d.DiscountPercentageAmount != 0 AND d.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.Customer,
                p.Code, p.Date, p.Currency, co.Currency, cc.Decimal, bp.Name, p.RateAmount, iag.SalesInvoice";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PENJUALAN
            //endregion

            $account = "";

            //region INSERT JOURNAL DISKON
            $account = "co.SalesDiscountAccount";
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.RateAmount,0), ' x ', FORMAT(p.DiscountAmount,2), ')')))", 
                "Account" => $account,      "BusinessPartner" => "NULL", 
                "Currency" => "p.Currency",            "Rate" => "IFNULL(p.RateAmount,1)",
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, p.DiscountAmount, 0, 
                ROUND(p.DiscountAmount * IFNULL(p.RateAmount,1), IFNULL(cc.Decimal,0)), 0,
                ROUND(p.DiscountAmount * IFNULL(p.RateAmount,1), IFNULL(cc.Decimal,0)) 
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.DiscountAmount != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL DISKON
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(pma1.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.PaymentRate,0), ' x ', FORMAT(p.PaymentAmount,2), ')')))", 
                "Account" => "pm1.Account",           "BusinessPartner" => "p.Customer", 
                "Currency" => "pma1.Currency",         "Rate" => "IFNULL(p.PaymentRate,1)",
            ]);
            $strField = "DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, p.PaymentAmount, 0, 
                p.PaymentAmountBase, 0, p.PaymentAmountBase
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.PaymentAmount != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(pma2.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.PaymentRate2,0), ' x ', FORMAT(p.PaymentAmount2,2), ')')))", 
                "Account" => "pm2.Account",           "BusinessPartner" => "p.Customer", 
                "Currency" => "pma2.Currency",         "Rate" => "IFNULL(p.PaymentRate2,1)",
            ]);
            $strField = "DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, p.PaymentAmount2, 0, 
                p.PaymentAmountBase2, 0, p.PaymentAmountBase2
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.PaymentAmount2 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(pma3.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.PaymentRate3,0), ' x ', FORMAT(p.PaymentAmount3,2), ')')))", 
                "Account" => "pm3.Account",           "BusinessPartner" => "p.Customer", 
                "Currency" => "pma3.Currency",         "Rate" => "IFNULL(p.PaymentRate3,1)",
            ]);
            $strField = "DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, p.PaymentAmount3, 0, 
                p.PaymentAmountBase3, 0, p.PaymentAmountBase3
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.PaymentAmount3 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(pma4.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.PaymentRate4,0), ' x ', FORMAT(p.PaymentAmount4,2), ')')))", 
                "Account" => "pm4.Account",           "BusinessPartner" => "p.Customer", 
                "Currency" => "pma4.Currency",         "Rate" => "IFNULL(p.PaymentRate4,1)",
            ]);
            $strField = "DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, p.PaymentAmount4, 0, 
                p.PaymentAmountBase4, 0, p.PaymentAmountBase4
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.PaymentAmount4 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(pma5.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.PaymentRate5,0), ' x ', FORMAT(p.PaymentAmount5,2), ')')))", 
                "Account" => "pm5.Account",           "BusinessPartner" => "p.Customer", 
                "Currency" => "pma5.Currency",         "Rate" => "IFNULL(p.PaymentRate5,1)",
            ]);
            $strField = "DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, p.PaymentAmount5, 0, 
                p.PaymentAmountBase5, 0, p.PaymentAmountBase5
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.PaymentAmount5 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
        });
    }

    public function postReturn($id)
    {
        DB::transaction(function() use ($id) {
            $user = Auth::user();
            $company = $user->CompanyObj;

            $pos = PointOfSale::with([
                'CustomerObj', 'Journals', 'Stocks'
            ])->findOrFail($id);

            if ($this->isPeriodClosed($pos->Date)) {
                $this->throwPeriodIsClosedError($pos->Date);
            }
            $pos->Journals()->delete();
            $pos->Stocks()->delete();


            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "PointOfSale" => "p.Oid",
                "Code" => "p.Code",                 "Date" => "p.Date", 
            ];
            $fromDetailTable =  "pospointofsaledetail d
                LEFT OUTER JOIN pospointofsale p ON p.Oid = d.PointOfSale
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.SalesProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'POS'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.Customer
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup
                LEFT OUTER JOIN mstitem ir ON ir.Oid = i.ItemStockReplacement";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL";
            $dateInitial = Carbon::parse($pos->Date)->format("d/M");
            //endregion

            // //region INSERT STOCK
            $arr = array_merge($arrDefault, [
                "Item" => "CASE WHEN ir.Oid IS NOT NULL THEN ir.Oid ELSE i.Oid END",    "BusinessPartner" => "p.Customer", 
                "Currency" => "p.Currency",            "Rate" => "IFNULL(p.RateAmount,1)",
                "Quantity" => "d.Quantity",            "Price" => "IFNULL(d.Amount,0)", "PriceBase" => "IFNULL(d.Amount * p.RateAmount,0)",
                "StockQuantity" => "d.Quantity",            "StockAmount" => "IFNULL(d.Amount * p.RateAmount,0)",
                "Warehouse" => "IFNULL(p.Warehouse, co.POSDefaultWarehouse)",
                "Status" => 's.Oid'
            ]);
            $query = "INSERT INTO trdtransactionstock (%s)
                SELECT %s
                FROM {$fromDetailTable} WHERE {$whereClause}  AND i.IsStock = 1 ";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT STOCK



            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "Status" => "s.Oid", 
                "PointOfSale" => "p.Oid",             "Source" => "'POS'",
                "Code" => "p.Code",                 "Date" => "p.Date", 
            ];
            $fromParentTable =  "pospointofsale p
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'POS'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN accaccount a ON a.Oid = co.SalesDiscountAccount
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.Customer
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup
                LEFT OUTER JOIN mstpaymentmethod pm1 ON pm1.Oid = p.PaymentMethod
                LEFT OUTER JOIN mstpaymentmethod pm2 ON pm2.Oid = p.PaymentMethod2
                LEFT OUTER JOIN mstpaymentmethod pm3 ON pm3.Oid = p.PaymentMethod3
                LEFT OUTER JOIN mstpaymentmethod pm4 ON pm4.Oid = p.PaymentMethod4
                LEFT OUTER JOIN mstpaymentmethod pm5 ON pm5.Oid = p.PaymentMethod5
                LEFT OUTER JOIN accaccount pma1 ON pma1.Oid = pm1.Account
                LEFT OUTER JOIN accaccount pma2 ON pma2.Oid = pm2.Account
                LEFT OUTER JOIN accaccount pma3 ON pma3.Oid = pm3.Account
                LEFT OUTER JOIN accaccount pma4 ON pma4.Oid = pm4.Account
                LEFT OUTER JOIN accaccount pma5 ON pma5.Oid = pm5.Account";
            $fromDetailTable =  "pospointofsaledetail d
                LEFT OUTER JOIN pospointofsale p ON p.Oid = d.PointOfSale
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.SalesProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'POS'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.Customer
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL";
            $dateInitial = Carbon::parse($pos->Date)->format("d/M");
            //endregion

            //region INSERT JOURNAL PENJUALAN
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.RateAmount,0), ' x ', FORMAT(SUM((d.Quantity * d.Amount) - d.DiscountAmount - d.DiscountPercentageAmount),2), ')')))", 
                "Account" => "IFNULL(iag.SalesIncome,co.ItemSalesIncome)",       "BusinessPartner" => "p.Customer", 
                "Currency" => "p.Currency",       "Rate" => "IFNULL(p.RateAmount,1)"
            ]);
            $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
                SELECT %s, 0, SUM((IFNULL(d.Quantity,0) * IFNULL(d.Amount,0)) - d.DiscountAmount - d.DiscountPercentageAmount),
                0, SUM(ROUND((IFNULL(d.Quantity,0) * IFNULL(d.Amount,0)) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0) * IFNULL(p.RateAmount,1), IFNULL(cc.Decimal,0))),
                SUM(ROUND((IFNULL(d.Quantity,0) * IFNULL(d.Amount,0)) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0) * IFNULL(p.RateAmount,1), IFNULL(cc.Decimal,0)))
                FROM {$fromDetailTable} WHERE {$whereClause} 
                AND (d.Quantity * d.Amount) - IFNULL(d.DiscountAmount,0) - IFNULL(d.DiscountPercentageAmount,0) != 0 AND d.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.Customer,
                p.Code, p.Date, p.Currency, co.Currency, cc.Decimal, bp.Name, p.RateAmount, iag.SalesInvoice";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PENJUALAN
            //endregion

            $account = "";

            //region INSERT JOURNAL DISKON
            $account = "co.SalesDiscountAccount";
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.RateAmount,0), ' x ', FORMAT(p.DiscountAmount,2), ')')))", 
                "Account" => $account,      "BusinessPartner" => "NULL", 
                "Currency" => "p.Currency",            "Rate" => "IFNULL(p.RateAmount,1)",
            ]);
            $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase,TotalBase)
                SELECT %s, p.DiscountAmount, 0, 
                ROUND(p.DiscountAmount * IFNULL(p.RateAmount,1), IFNULL(cc.Decimal,0)), 0,
                ROUND(p.DiscountAmount * IFNULL(p.RateAmount,1), IFNULL(cc.Decimal,0)) 
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.DiscountAmount != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL DISKON
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(pma1.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.PaymentRate,0), ' x ', FORMAT(p.PaymentAmount,2), ')')))", 
                "Account" => "pm1.Account",           "BusinessPartner" => "p.Customer", 
                "Currency" => "pma1.Currency",         "Rate" => "IFNULL(p.PaymentRate,1)",
            ]);
            $strField = "CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, p.PaymentAmount, 0, 
                p.PaymentAmountBase, 0, p.PaymentAmountBase
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.PaymentAmount != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(pma2.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.PaymentRate2,0), ' x ', FORMAT(p.PaymentAmount2,2), ')')))", 
                "Account" => "pm2.Account",           "BusinessPartner" => "p.Customer", 
                "Currency" => "pma2.Currency",         "Rate" => "IFNULL(p.PaymentRate2,1)",
            ]);
            $strField = "CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, p.PaymentAmount2, 0, 
                p.PaymentAmountBase2, 0, p.PaymentAmountBase2
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.PaymentAmount2 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(pma3.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.PaymentRate3,0), ' x ', FORMAT(p.PaymentAmount3,2), ')')))", 
                "Account" => "pm3.Account",           "BusinessPartner" => "p.Customer", 
                "Currency" => "pma3.Currency",         "Rate" => "IFNULL(p.PaymentRate3,1)",
            ]);
            $strField = "CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, p.PaymentAmount3, 0, 
                p.PaymentAmountBase3, 0, p.PaymentAmountBase3
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.PaymentAmount3 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(pma4.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.PaymentRate4,0), ' x ', FORMAT(p.PaymentAmount4,2), ')')))", 
                "Account" => "pm4.Account",           "BusinessPartner" => "p.Customer", 
                "Currency" => "pma4.Currency",         "Rate" => "IFNULL(p.PaymentRate4,1)",
            ]);
            $strField = "CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, p.PaymentAmount4, 0, 
                p.PaymentAmountBase4, 0, p.PaymentAmountBase4
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.PaymentAmount4 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
            
            //region INSERT JOURNAL PAYMENT METHOD    
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(pma5.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.PaymentRate5,0), ' x ', FORMAT(p.PaymentAmount5,2), ')')))", 
                "Account" => "pm5.Account",           "BusinessPartner" => "p.Customer", 
                "Currency" => "pma5.Currency",         "Rate" => "IFNULL(p.PaymentRate5,1)",
            ]);
            $strField = "CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase";            
            $query = "INSERT INTO accjournal (%s, {$strField})
                SELECT %s, p.PaymentAmount5, 0, 
                p.PaymentAmountBase5, 0, p.PaymentAmountBase5
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.PaymentAmount5 != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL PAYMENT METHOD
            //endregion
        });
    }

    public function unpost($id)
    {
        DB::transaction(function() use ($id) {
            $pos = PointOfSale::findOrFail($id);
            if ($this->isPeriodClosed($pos->Date)) {
                $this->throwPeriodIsClosedError($pos->Date);
            }
            $pos->Journals()->delete();
            $pos->Stocks()->delete();
        });
    }
}