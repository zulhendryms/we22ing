<?php

namespace App\Core\Travel\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Accounting\Services\JournalService;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\Status;
use App\Core\Internal\Entities\JournalType;
use App\Core\Travel\Entities\TravelTransaction;

class TravelTransactionARInvoiceService
{
    public function post(TravelTransaction $id)
    {
        DB::transaction(function() use ($id) {
            $user = Auth::user();
            $company = $user->CompanyObj;

            $travelTransaction = TravelTransaction::findOrFail($id->Oid);
            $travelTransaction->Journals()->where('Source','Sales-Invoice')->delete();

            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "Status" => "s.Oid", 
                "TravelTransaction" => "p.Oid",             "Source" => "'Sales-Invoice'",
                "Code" => "p.Code",                 "Date" => "p.Date", 
            ];
            $fromParentTable =  "pospointofsale p
                LEFT OUTER JOIN traveltransaction trv ON trv.Oid = p.Oid
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'SINV'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.Customer
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id->Oid}' AND p.GCRecord IS NULL";
            //endregion 
            
            $fromDetailTable2 = "
                LEFT OUTER JOIN trvtransactiondetail td ON p.Oid = td.TravelTransaction
                LEFT OUTER JOIN mstitem i ON i.Oid = td.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accapinvoice api ON api.Oid = td.APInvoice";
            $fromDetailTable2 = $fromParentTable.$fromDetailTable2;

            //region INSERT JOURNAL BALEK AP INVOICE - WIP KE BIAYA
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
                "Account" => "iag.PurchaseExpense",                   "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",            "Rate" => "1",
                "TravelTransactionReport" => 'td.TravelTransaction',
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1))
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND td.PurchaseTotal != 0 AND td.GCRecord IS NULL AND td.APInvoice IS NOT NULL AND td.AccountStock IS NULL
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.Customer, 
                p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, iag.PurchaseExpense";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            
            DB::insert($query); //region INSERT JOURNAL BALEK AP INVOICE - WIP KE BIAYA 1
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
                "Account" => "iag.PurchaseProduction",                   "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",            "Rate" => "1",
                "TravelTransactionReport" => 'td.TravelTransaction',
            ]);
            $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
                SELECT %s, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1))
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND td.PurchaseTotal != 0 AND td.GCRecord IS NULL AND td.APInvoice IS NOT NULL AND td.AccountStock IS NULL
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.Customer, 
                p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, iag.PurchaseProduction";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //region INSERT JOURNAL BALEK AP INVOICE - WIP KE BIAYA 2
            //endregion

            //region INSERT JOURNAL BALEK AP INVOICE - PERSEDIAAN KE BIAYA
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
                "Account" => "iag.PurchaseExpense",                   "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",            "Rate" => "1",
                "TravelTransactionReport" => 'td.TravelTransaction',
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1))
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND td.PurchaseTotal != 0 AND td.GCRecord IS NULL AND td.APInvoice IS NULL AND td.AccountStock IS NOT NULL
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.Customer, 
                p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, iag.PurchaseExpense";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //region INSERT JOURNAL BALEK AP INVOICE - PERSEDIAAN KE BIAYA 1
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
                "Account" => "td.AccountStock",                   "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",            "Rate" => "1",
                "TravelTransactionReport" => 'td.TravelTransaction',
            ]);
            $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
                SELECT %s, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
                    SUM(td.PurchaseTotal * IFNULL(api.Rate, 1))
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND td.PurchaseTotal != 0 AND td.GCRecord IS NULL AND td.APInvoice IS NULL AND td.AccountStock IS NOT NULL
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.Customer, 
                p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, iag.PurchaseProduction";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //region INSERT JOURNAL BALEK AP INVOICE - PERSEDIAAN KE BIAYA 2
            //endregion



            $fromDetailTable2 = "
                LEFT OUTER JOIN acccashbank cb ON p.Oid = cb.TravelTransaction
                LEFT OUTER JOIN acccashbankdetail cbd ON cb.Oid = cbd.CashBank";
            $fromDetailTable2 = $fromParentTable.$fromDetailTable2;

            //region INSERT JOURNAL BALEK EXPENSE - WIP KE BIAYA 1
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
                "Account" => "cbd.Account",                   "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",            "Rate" => "1",
                "TravelTransactionReport" => 'cb.TravelTransaction',
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND cbd.AmountInvoiceBase != 0 AND cbd.GCRecord IS NULL AND cb.Type = 1
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.Customer, 
                p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, cbd.Account";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //region INSERT JOURNAL BALEK EXPENSE - WIP KE BIAYA 1            
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
                "Account" => "co.ExpenseInProgress",                   "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",            "Rate" => "1",
                "TravelTransactionReport" => 'cb.TravelTransaction',
            ]);
            $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
                SELECT %s, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND cbd.AmountInvoiceBase != 0 AND cbd.GCRecord IS NULL AND cb.Type = 1
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.Customer, 
                p.Code, p.Date, co.Currency, cc.Decimal, bp.Name";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //region INSERT JOURNAL BALEK EXPENSE - WIP KE BIAYA 2
            //endregion


            //region INSERT JOURNAL BALEK INCOME - WIP KE BIAYA
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
                "Account" => "cbd.Account",                   "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",            "Rate" => "1",
                "TravelTransactionReport" => 'cb.TravelTransaction',
            ]);
            $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
                SELECT %s, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND cbd.AmountInvoiceBase != 0 AND cbd.GCRecord IS NULL AND cb.Type = 0
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.Customer, 
                p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, cbd.Account";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //region INSERT JOURNAL BALEK INCOME - WIP KE BIAYA 1            
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
                "Account" => "co.IncomeInProgress",                   "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",            "Rate" => "1",
                "TravelTransactionReport" => 'cb.TravelTransaction',
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND cbd.AmountInvoiceBase != 0 AND cbd.GCRecord IS NULL AND cb.Type = 0
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.Customer, 
                p.Code, p.Date, co.Currency, cc.Decimal, bp.Name";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //region INSERT JOURNAL BALEK INCOME - WIP KE BIAYA 2
            //endregion

            // //region INSERT JOURNAL TRAVELTRANSACTION
            // $fromDetailTable2 = "
            // LEFT OUTER JOIN pospointofsale pos ON d.Oid = pos.Oid";
            // $fromDetailTable2 = $fromDetailTable.$fromDetailTable2;
            // $arr = array_merge($arrDefault, [
            //     "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //     "Account" => "co.ItemSalesIncome",                   "BusinessPartner" => "NULL", 
            //     "Currency" => "p.Currency",            "Rate" => "IFNULL(p.Rate,1)",
            // ]);
            // $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
            //     SELECT %s, pos.TotalAmount, 0, 
            //     ROUND(pos.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)), 0, 
            //     ROUND(pos.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)) 
            //     FROM {$fromDetailTable2} WHERE {$whereClause} 
            //     AND pos.TotalAmount != 0 AND pos.GCRecord IS NULL
            //     GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //     p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate";
            // $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            // DB::insert($query); //region INSERT JOURNAL TRAVELTRANSACTION 1
            // //endregion

            //region INSERT JOURNAL TRAVELTRANSACTION
            // $fromDetailTable2 = "
            // LEFT OUTER JOIN pospointofsale pos ON d.Oid = pos.Oid";
            // $fromDetailTable2 = $fromDetailTable.$fromDetailTable2;
            // $arr = array_merge($arrDefault, [
            //     "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //     "Account" => "co.ItemSalesIncome",                   "BusinessPartner" => "NULL", 
            //     "Currency" => "p.Currency",            "Rate" => "IFNULL(p.Rate,1)",
            // ]);
            // $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
            //     SELECT %s, pos.TotalAmount, 0, 
            //     ROUND(pos.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)), 0, 
            //     ROUND(pos.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)) 
            //     FROM {$fromDetailTable2} WHERE {$whereClause} 
            //     AND pos.TotalAmount != 0 AND pos.GCRecord IS NULL
            //     GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //     p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate";
            // $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            // DB::insert($query); //region INSERT JOURNAL TRAVELTRANSACTION 1
            //endregion

            $fromDetailTable2 = "
            LEFT OUTER JOIN accarinvoice ar ON p.Oid = ar.TravelTransaction";
            $fromDetailTable2 = $fromParentTable.$fromDetailTable2;

            //region INSERT JOURNAL AR INVOICE - WIP KE PENDAPATAN
            $arInvoiceAmt = "(ar.TotalAmount + ar.AdditionalAmount - ar.DiscountAmount)";
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': Commission ')", 
                "Account" => "'".$company->IncomeInProgress."'",       "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",         "Rate" => "ar.Rate",
                "TravelTransactionReport" => 'p.Oid',
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, SUM({$arInvoiceAmt}), 0, 
                SUM({$arInvoiceAmt} * ar.Rate), 0, SUM({$arInvoiceAmt} * ar.Rate)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND {$arInvoiceAmt} != 0 AND ar.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL AR INVOICE - WIP KE PENDAPATAN 1
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': Commission ')", 
                "Account" => "co.ItemSalesIncome", "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",         "Rate" => "1",
                "TravelTransactionReport" => 'p.Oid',
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 0, SUM({$arInvoiceAmt}), 0, SUM({$arInvoiceAmt} * ar.Rate), SUM({$arInvoiceAmt} * ar.Rate)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND {$arInvoiceAmt} != 0 AND ar.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL AR INVOICE - WIP KE PENDAPATAN 2
            //endregion

            //region INSERT JOURNAL AR INVOICE - WIP KE PENDAPATAN
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': Commission ')", 
                "Account" => "'".$company->IncomeInProgress."'",       "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",         "Rate" => "ar.Rate",
                "TravelTransactionReport" => 'p.Oid',
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, SUM(ar.AdditionalAmount), 0, SUM(ar.AdditionalAmount * ar.Rate), 0, SUM(ar.AdditionalAmount * ar.Rate)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND ar.AdditionalAmount != 0 AND ar.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, ar.AdditionalAccount";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL AR INVOICE - WIP KE PENDAPATAN 1
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': Commission ')", 
                "Account" => "ar.AdditionalAccount",       "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",         "Rate" => "1",
                "TravelTransactionReport" => 'p.Oid',
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 0, SUM(ar.AdditionalAmount), 0, SUM(ar.AdditionalAmount * ar.Rate), SUM(ar.AdditionalAmount * ar.Rate)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND ar.AdditionalAmount != 0 AND ar.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, ar.AdditionalAccount";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL AR INVOICE - WIP KE PENDAPATAN 2
            //endregion

            //region INSERT JOURNAL AR INVOICE - WIP KE PENDAPATAN
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': Commission ')", 
                "Account" => "'".$company->IncomeInProgress."'",       "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",         "Rate" => "1",
                "TravelTransactionReport" => 'p.Oid',
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, SUM(ar.DiscountAmount), 0, SUM(ar.DiscountAmount * ar.Rate), 0, SUM(ar.DiscountAmount * ar.Rate)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND ar.DiscountAmount != 0 AND ar.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, ar.DiscountAccount";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL AR INVOICE - WIP KE PENDAPATAN 1
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': Commission ')", 
                "Account" => "ar.DiscountAccount",       "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",         "Rate" => "1",
                "TravelTransactionReport" => 'p.Oid',
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 0, SUM(ar.DiscountAmount), 0, SUM(ar.DiscountAmount * ar.Rate), SUM(ar.DiscountAmount * ar.Rate)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND ar.DiscountAmount != 0 AND ar.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, ar.DiscountAccount";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL AR INVOICE - WIP KE PENDAPATAN 2
            //endregion
        
            $fromDetailTable2 = "
                LEFT OUTER JOIN trvtransactiondetail td ON p.Oid = td.TravelTransaction
                LEFT OUTER JOIN mstitem i ON i.Oid = td.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accapinvoice api ON api.Oid = td.APInvoice";
            $fromDetailTable2 = $fromParentTable.$fromDetailTable2;

            //region INSERT JOURNAL BALEK KOMISI - WIP KE PENDAPATAN
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': Commission ')", 
                "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",         "Rate" => "1",
                "TravelTransactionReport" => 'p.Oid',
            ]);
            $query = "INSERT INTO accjournal (%s, Account, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 
                IF(SUM(td.CommAmountIncomeExpense) < 0, '{$company->IncomeInProgress}', '{$company->ExpenseInProgress}'),
                IF(SUM(td.CommAmountIncomeExpense) < 0, td.CommAmountIncomeExpense * -1, 0),
                IF(SUM(td.CommAmountIncomeExpense) > 0, td.CommAmountIncomeExpense, 0),
                IF(SUM(td.CommAmountIncomeExpense) < 0, td.CommAmountIncomeExpense * -1, 0),
                IF(SUM(td.CommAmountIncomeExpense) > 0, td.CommAmountIncomeExpense, 0),
                SUM(td.CommAmountIncomeExpense)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND td.CommAmountIncomeExpense != 0 AND td.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, td.CommAccountIncomeExpense";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            logger($query);
            DB::insert($query); //region INSERT JOURNAL BALEK KOMISI - WIP KE PENDAPATAN 1 
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': Commission ')", 
                "Account" => "td.CommAccountIncomeExpense",       "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",         "Rate" => "1",
                "TravelTransactionReport" => 'p.Oid',
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 
                IF(SUM(td.CommAmountIncomeExpense) > 0, td.CommAmountIncomeExpense, 0),
                IF(SUM(td.CommAmountIncomeExpense) < 0, td.CommAmountIncomeExpense * -1, 0),
                IF(SUM(td.CommAmountIncomeExpense) > 0, td.CommAmountIncomeExpense, 0),
                IF(SUM(td.CommAmountIncomeExpense) < 0, td.CommAmountIncomeExpense * -1, 0),
                SUM(td.CommAmountIncomeExpense)
                FROM {$fromDetailTable2} WHERE {$whereClause} 
                AND td.CommAmountIncomeExpense != 0 AND td.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, td.CommAccountIncomeExpense";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //region INSERT JOURNAL BALEK KOMISI - WIP KE PENDAPATAN 2
            //endregion

            TravelTransaction::where('Oid', $id->Oid)
            ->update([
                'StatusARInvoice' => Status::posted()->value('Oid'),
            ]);
        });
    }

    public function unpost(TravelTransaction $id)
    {        
        DB::transaction(function() use ($id) {
            $travelTransaction = TravelTransaction::findOrFail($id->Oid);
            $travelTransaction->Journals()->where('Source','Sales-Invoice')->delete();
            TravelTransaction::where('Oid', $id->Oid)
            ->update([
                'StatusARInvoice' => Status::entry()->value('Oid'),
            ]);
        });
    }
}