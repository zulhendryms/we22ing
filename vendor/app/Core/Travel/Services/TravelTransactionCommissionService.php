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
use App\Core\Travel\Entities\TravelTransactionCommission;

class TravelTransactionCommissionService
{
    public function post(TravelTransaction $id)
    {
        DB::transaction(function() use ($id) {
            $user = Auth::user();
            $company = $user->CompanyObj;

            $travelTransaction = TravelTransaction::findOrFail($id->Oid);
            $travelTransaction->Journals()->where('Source','Travel-Comms')->delete();            
            $travelTransaction->APCommissions()->delete();
            $travelTransaction->ARCommissions()->delete();
            
            $fromDetailTable =  "trvtransactiondetail d
                LEFT OUTER JOIN traveltransaction tt ON tt.Oid = d.TravelTransaction
                LEFT OUTER JOIN pospointofsale p ON p.Oid = tt.Oid
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency";
            $whereClause = "p.Company = '{$company->Oid}' AND tt.Oid = '{$id->Oid}' AND d.IsCommission = true AND d.GCRecord IS NULL";

            //INSERT AP INVOICE AR INVOICE
            $i = 0;
            foreach ($travelTransaction->Details as $detail) {
                if ($detail->IsCommission) {
                    $fromDetailTable2 = "
                        LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = d.BusinessPartner
                        LEFT OUTER JOIN mstbusinesspartneraccountgroup bpac ON bpac.Oid = bp.BusinessPartnerAccountGroup
                        LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                        LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup";
                    $fromDetailTable2 = $fromDetailTable.$fromDetailTable2;
                    if ($detail->CommIsAPCost) {
                        $arrDefault = [
                            "Oid" => "UUID()",                  "Company" => "co.Oid",  "CreatedAt" => "NOW()",
                            "Code" => "CONCAT(bp.Code,'-',DATE_FORMAT(now(),'%Y%m%d%H%i'),{$i})", "Date" => "now()", 
                            "BusinessPartner" => "d.BusinessPartner",          "Currency" => "d.PurchaseCurrency",
                            "Account" => "bpac.PurchaseInvoice",                "Status" => "s.Oid", 
                            "TotalAmount" => "SUM(CommAmountARAP)",    "TotalBase" => "SUM(CommAmountARAP)",
                            "AdditionalAccount" => "iag.PurchaseExpense",     "AdditionalAmount" => "SUM(CommAmountARAP)",
                            "TravelCommission" => "tt.Oid"
                        ];
                        $query = "INSERT INTO accapinvoice (%s)
                            SELECT %s
                            FROM {$fromDetailTable2} WHERE {$whereClause} 
                            AND d.CommAmountARAP != 0 AND d.GCRecord IS NULL 
                            GROUP BY co.Oid, d.BusinessPartner, d.PurchaseCurrency, iag.PurchaseExpense, bpac.PurchaseInvoice";
                        $query = sprintf($query, implode(',', array_keys($arrDefault)), implode(',', $arrDefault));
                        DB::insert($query); //INSERT JOURNAL KOMISI PIUTANG
                        $i += 1;
                    }
                    if ($detail->CommIsARCommission) {                        
                        $arrDefault = [
                            "Oid" => "UUID()",                  "Company" => "co.Oid",  "CreatedAt" => "NOW()",
                            "Code" => "CONCAT(bp.Code,'-',DATE_FORMAT(now(),'%Y%m%d%H%i'),{$i})", "Date" => "now()", 
                            "BusinessPartner" => "d.BusinessPartner",          "Currency" => "d.SalesCurrency",
                            "Account" => "bpac.SalesInvoice",                "Status" => "s.Oid", 
                            "TotalAmount" => "SUM(CommAmountARAP)",    "TotalBase" => "SUM(CommAmountARAP)",
                            "AdditionalAccount" => "iag.SalesIncome",     "AdditionalAmount" => "SUM(CommAmountARAP)",
                            "TravelCommission" => "tt.Oid"
                        ];
                        $query = "INSERT INTO accapinvoice (%s)
                            SELECT %s
                            FROM {$fromDetailTable2} WHERE {$whereClause} 
                            AND d.CommAmountARAP != 0 AND d.GCRecord IS NULL 
                            GROUP BY co.Oid, d.BusinessPartner, d.SalesCurrency, iag.SalesIncome, bpac.SalesInvoice";
                        $query = sprintf($query, implode(',', array_keys($arrDefault)), implode(',', $arrDefault));
                        DB::insert($query); //INSERT JOURNAL KOMISI PIUTANG
                        $i += 1;
                    }
                }
            }

            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "co.Oid",  "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "Status" => "s.Oid", 
                "TravelTransaction" => "tt.Oid",    "Source" => "'Travel-Comms'",
                "Code" => "p.Code",                 "Date" => "p.Date", 
            ];
            $fromParentTable =  "traveltransaction tt
                LEFT OUTER JOIN pospointofsale p ON p.Oid = tt.Oid
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'TRVCOM'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency";
            $fromDetailTable =  "trvtransactiondetail d
                LEFT OUTER JOIN traveltransaction tt ON tt.Oid = d.TravelTransaction
                LEFT OUTER JOIN pospointofsale p ON p.Oid = tt.Oid
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'TRVCOM'
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency";
            $whereClause = "p.Company = '{$company->Oid}' AND tt.Oid = '{$id->Oid}' AND d.IsCommission = true AND d.GCRecord IS NULL";
            //endregion

            //region INSERT JOURNAL KOMISI HUTANG PIUTANG
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': Commission ')", 
                "Account" => "d.CommAccountARAP",       "BusinessPartner" => "d.BusinessPartner", 
                "Currency" => "co.Currency",         "Rate" => "1",
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 
                IF(SUM(d.CommAmountARAP) > 0, SUM(d.CommAmountARAP), 0),
                IF(SUM(d.CommAmountARAP) < 0, SUM(d.CommAmountARAP) * -1, 0),
                IF(SUM(d.CommAmountARAP) > 0, SUM(d.CommAmountARAP), 0),
                IF(SUM(d.CommAmountARAP) < 0, SUM(d.CommAmountARAP) * -1, 0),
                SUM(d.CommAmountARAP)
                FROM {$fromDetailTable} WHERE {$whereClause} 
                AND d.CommAmountARAP != 0 AND d.GCRecord IS NULL 
                GROUP BY jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, d.CommAccountARAP";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL KOMISI PIUTANG
            //endregion
            
            //region INSERT JOURNAL KOMISI BIAYA PENDAPATAN
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': Commission ')", 
                "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",         "Rate" => "1",
                "TravelTransactionReport" => 'd.TravelTransaction',
            ]);
            $query = "INSERT INTO accjournal (%s, Account, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 
                IF(SUM(d.CommAmountIncomeExpense) > 0, co.IncomeInProgress, co.ExpenseInProgress),
                IF(SUM(d.CommAmountIncomeExpense) > 0, SUM(d.CommAmountIncomeExpense), 0),
                IF(SUM(d.CommAmountIncomeExpense) < 0, SUM(d.CommAmountIncomeExpense) * -1, 0),
                IF(SUM(d.CommAmountIncomeExpense) > 0, SUM(d.CommAmountIncomeExpense), 0),
                IF(SUM(d.CommAmountIncomeExpense) < 0, SUM(d.CommAmountIncomeExpense) * -1, 0),
                SUM(d.CommAmountIncomeExpense)
                FROM {$fromDetailTable} WHERE {$whereClause} 
                AND d.CommAmountIncomeExpense != 0 AND d.GCRecord IS NULL 
                GROUP BY jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            logger($query);
            DB::insert($query); //INSERT JOURNAL KOMISI PENDAPATAN UM
            //endregion

            //region INSERT JOURNAL KOMISI KAS BANK
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': Commission ')", 
                "Account" => "tt.CommissionAccountCashBank",       "BusinessPartner" => "NULL", 
                "Currency" => "co.Currency",         "Rate" => "1",
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 
                IF(SUM(d.CommAmountCashBank) > 0, SUM(d.CommAmountCashBank), 0),
                IF(SUM(d.CommAmountCashBank) < 0, SUM(d.CommAmountCashBank) * -1, 0),
                IF(SUM(d.CommAmountCashBank) > 0, SUM(d.CommAmountCashBank), 0),
                IF(SUM(d.CommAmountCashBank) < 0, SUM(d.CommAmountCashBank) * -1, 0),
                SUM(d.CommAmountCashBank)
                FROM {$fromDetailTable} WHERE {$whereClause} 
                AND d.CommAmountCashBank != 0 AND d.GCRecord IS NULL 
                GROUP BY jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, d.TravelTransaction";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL KAS BANK
            //endregion

            TravelTransaction::where('Oid', $id->Oid)
            ->update([
                'StatusCommission' => Status::posted()->value('Oid'),
            ]);
        });
    }

    public function unpost(TravelTransaction $id)
    {        
        DB::transaction(function() use ($id) {
            $travelTransaction = TravelTransaction::findOrFail($id->Oid);
            $travelTransaction->Journals()->where('Source','Travel-Comms')->delete();   
            $travelTransaction->APCommissions()->delete();
            $travelTransaction->ARCommissions()->delete();
            TravelTransaction::where('Oid', $id->Oid)
            ->update([
                'StatusCommission' => Status::entry()->value('Oid'),
            ]);
        });
    }
}
