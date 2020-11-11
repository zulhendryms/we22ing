<?php

namespace App\Core\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Accounting\Services\JournalService;
use App\Core\Internal\Entities\Status;
use App\Core\Trading\Entities\SalesInvoice;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Travel\Entities\TravelTransaction;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\JournalType;
use Illuminate\Support\Facades\Auth;

class SalesInvoiceService extends JournalObjectService
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

            $salesInvoice = SalesInvoice::with([
                'BusinessPartnerObj',
                'AccountObj'
            ])->where('Oid',$id)->first();
            $company = $salesInvoice->CompanyObj;

            if ($this->isPeriodClosed($salesInvoice->Date)) {
                $this->throwPeriodIsClosedError($salesInvoice->Date);
            }
            $salesInvoice->Journals()->delete();
            $salesInvoice->Stocks()->delete();

            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "SalesInvoice" => "p.Oid",
                "Code" => "p.Code",                 "Date" => "p.Date", 
                "Note" => "p.Note",
            ];
            $fromDetailTable =  "trdsalesinvoicedetail d
                LEFT OUTER JOIN trdsalesinvoice p ON p.Oid = d.SalesInvoice
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.SalesProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'SINV'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup
                LEFT OUTER JOIN mstitem ir ON ir.Oid = i.ItemStockReplacement";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL";
            $dateInitial = Carbon::parse($salesInvoice->Date)->format("d/M");
            //endregion

            // //region INSERT STOCK
            $account = "p.AdditionalAccount";
            $arr = array_merge($arrDefault, [
                "Item" => "CASE WHEN ir.Oid IS NOT NULL THEN ir.Oid ELSE i.Oid END",    "BusinessPartner" => "p.BusinessPartner", 
                "Currency" => "p.Currency",            "Rate" => "IFNULL(p.Rate,1)",
                "Quantity" => "d.Quantity",            "Price" => "IFNULL(d.Price,0)", "PriceBase" => "IFNULL(d.Price * p.Rate,0)",
                "StockQuantity" => "d.Quantity * -1",            "StockAmount" => "0",
                "Warehouse" => "IFNULL(p.Warehouse, co.POSDefaultWarehouse)",
                "Status" => 's.Oid'
            ]);
            $query = "INSERT INTO trdtransactionstock (%s)
                SELECT %s
                FROM {$fromDetailTable} WHERE {$whereClause} ";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT STOCK

            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",          "Status" => "s.Oid", 
                "SalesInvoice" => "p.Oid",             "Source" => "'Sales-Invoice'",
                "Code" => "p.Code",                 "Date" => "p.Date", 
            ];
            $fromParentTable =  "trdsalesinvoice p
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'SINV'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN accaccount a ON a.Oid = p.DiscountAccount
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup";
            // $fromDetailTable =  "traveltransaction d
            //     LEFT OUTER JOIN accSalesInvoice p ON p.TravelTransaction = d.Oid
            //     LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'SINV'
            //     LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
            //     LEFT OUTER JOIN company co ON co.Oid = p.Company
            //     LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
            //     LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
            //     LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
            //     LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup";
            $fromDetailTable =  "trdsalesinvoicedetail d
                LEFT OUTER JOIN trdsalesinvoice p ON p.Oid = d.SalesInvoice
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.SalesProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'PINV'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
                LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup
                LEFT OUTER JOIN sysjournaltype jtcogs ON jtcogs.Code = 'COGS'";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL";
            $dateInitial = Carbon::parse($salesInvoice->Date)->format("d/M");
            //endregion

            
            // //BEGIN OF : INSERT JOURNAL STOCK---------------------------------------------------------------- 
            // $arrDefault = [
            //     "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
            //     "JournalType" => "jtogs.Oid",          "Status" => "s.Oid", 
            //     "SalesInvoice" => "p.Oid",             "Source" => "'Sales-Invoice'",
            //     "Code" => "p.Code",                 "Date" => "p.Date", 
            // ];           
            // $arr = array_merge($arrDefault, [
            //     "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(SUM(d.TotalAmount),2), ')')))", 
            //     "Account" => "IFNULL(iag.PurchaseExpense,co.ItemPurchaseExpense)",       "BusinessPartner" => "null", 
            //     "Currency" => "p.Currency",       "Rate" => "IFNULL(p.Rate,1)"
            // ]);
            // $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
            //     SELECT %s, 0, SUM(d.TotalAmount), 0,
            //     SUM(ROUND(d.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0))),
            //     SUM(ROUND(d.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)))
            //     FROM {$fromDetailTable} WHERE {$whereClause} 
            //     AND d.TotalAmount != 0 AND d.GCRecord IS NULL 
            //     GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date,
            //     p.Code, p.Date, p.Currency, co.Currency, cc.Decimal, bp.Name, p.Rate, iag.PurchaseExpense";
            // $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            // logger($query);
            // DB::insert($query); //INSERT JOURNAL WIP

                        
            //ENDING OF : INSERT JOURNAL STOCK----------------------------------------------------------------


            //region INSERT JOURNAL INVOICE DETAIL
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(SUM(d.TotalAmount),2), ')')))", 
                "Account" => "IFNULL(iag.SalesInvoice,co.ItemSalesIncome)",       "BusinessPartner" => "p.BusinessPartner", 
                "Currency" => "p.Currency",       "Rate" => "IFNULL(p.Rate,1)"
            ]);
            $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
                SELECT %s, 0, SUM(IFNULL(d.TotalAmount,0)), 0,
                SUM(ROUND(IFNULL(d.TotalAmount,0) * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0))),
                SUM(ROUND(IFNULL(d.TotalAmount,0) * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)))
                FROM {$fromDetailTable} WHERE {$whereClause} 
                AND d.TotalAmount != 0 AND d.GCRecord IS NULL 
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner,
                p.Code, p.Date, p.Currency, co.Currency, cc.Decimal, bp.Name, p.Rate, iag.SalesInvoice";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            logger($query);
            DB::insert($query); //INSERT JOURNAL WIP
            //endregion

            $account = "";
            // $isBooking = isset($salesInvoice->TravelTransaction);
            $isBooking = isset($salesInvoice->TravelTransaction);

            if ($salesInvoice->IsPrimaryInvoice == true && isset($salesInvoice->TravelTransaction)) {
                // throw new \Exception($err->getMessage());
                //cek semua TravelTransaction->TravelTransactionDetails->Status NOT IN ('Cancel', 'Complete')
                //cek semua TravelTransaction->TravelTransactionDetails->APInvoice NULL
                //cek semua TravelTransaction->TravelTransactionCommissions->Status NOT IN ('Cancel', 'Complete')
                //cek semua TravelTransaction->CashBank->Status NOT IN ('Cancel', 'Complete')

                $travelTransaction = $salesInvoice->TravelTransactionObj->PointOfSaleObj;
                if ($travelTransaction->Customer == $salesInvoice->BusinessPartner && $travelTransaction->Currency == $salesInvoice->Currency) {
                    //region INSERT JOURNAL TRAVELTRANSACTION ItemSalesIncome
                    $fromDetailTable2 = "
                        LEFT OUTER JOIN pospointofsale pos ON d.Oid = pos.Oid";
                    $fromDetailTable2 = $fromDetailTable.$fromDetailTable2;
                    $arr = array_merge($arrDefault, [
                        "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
                        "Account" => "co.IncomeInProgress",     "BusinessPartner" => "NULL", 
                        "Currency" => "p.Currency",            "Rate" => "IFNULL(p.Rate,1)",
                    ]);
                    $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
                        SELECT %s, pos.TotalAmount, 0, 
                        ROUND(pos.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)), 0, 
                        ROUND(pos.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)) 
                        FROM {$fromDetailTable2} WHERE {$whereClause} 
                        AND pos.TotalAmount != 0 AND pos.GCRecord IS NULL
                        GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
                        p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate";
                    $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                    DB::insert($query); //region INSERT JOURNAL TRAVELTRANSACTION 1
                    //endregion
                }
            }

            // if ($isBooking) {
            //     foreach ($salesInvoice->Details as $p) {
            //         $travelTransaction = TravelTransaction::findOrFail($p->Oid);
            //         $fromDetailTable2 = "
            //             LEFT OUTER JOIN trvtransactiondetail td ON d.Oid = td.TravelTransaction
            //             LEFT OUTER JOIN mstitem i ON i.Oid = td.Item
            //             LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
            //             LEFT OUTER JOIN accapinvoice api ON api.Oid = td.APInvoice";
            //         $fromDetailTable2 = $fromDetailTable.$fromDetailTable2;

            //         //region INSERT JOURNAL BALEK WIP KE BIAYA ADA INVOICE
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //             "Account" => "iag.PurchaseExpense",                   "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",            "Rate" => "1",
            //             "TravelTransactionReport" => 'td.TravelTransaction',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
            //             SELECT %s, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1))
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND td.PurchaseTotal != 0 AND td.GCRecord IS NULL AND td.APInvoice IS NOT NULL AND td.AccountStock IS NULL
            //             GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //             p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate, iag.PurchaseExpense";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //region INSERT JOURNAL BALEK WIP KE BIAYA 1
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //             "Account" => "iag.PurchaseProduction",                   "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",            "Rate" => "1",
            //             "TravelTransactionReport" => 'td.TravelTransaction',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
            //             SELECT %s, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1))
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND td.PurchaseTotal != 0 AND td.GCRecord IS NULL AND td.APInvoice IS NOT NULL AND td.AccountStock IS NULL
            //             GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //             p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate, iag.PurchaseProduction";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //region INSERT JOURNAL BALEK WIP KE BIAYA 2
            //         //endregion

            //         //region INSERT JOURNAL BALEK WIP KE BIAYA ADA PERSEDIAAN
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //             "Account" => "iag.PurchaseExpense",                   "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",            "Rate" => "1",
            //             "TravelTransactionReport" => 'td.TravelTransaction',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
            //             SELECT %s, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1))
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND td.PurchaseTotal != 0 AND td.GCRecord IS NULL AND td.APInvoice IS NULL AND td.AccountStock IS NOT NULL
            //             GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //             p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate, iag.PurchaseExpense";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //region INSERT JOURNAL BALEK WIP KE BIAYA 1
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //             "Account" => "td.AccountStock",                   "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",            "Rate" => "1",
            //             "TravelTransactionReport" => 'td.TravelTransaction',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
            //             SELECT %s, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1)), 0, 
            //                 SUM(td.PurchaseTotal * IFNULL(api.Rate, 1))
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND td.PurchaseTotal != 0 AND td.GCRecord IS NULL AND td.APInvoice IS NULL AND td.AccountStock IS NOT NULL
            //             GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //             p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate, iag.PurchaseProduction";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //region INSERT JOURNAL BALEK WIP KE BIAYA 2
            //         //endregion



            //         $fromDetailTable2 = "
            //             LEFT OUTER JOIN acccashbank cb ON d.Oid = cb.TravelTransaction
            //             LEFT OUTER JOIN acccashbankdetail cbd ON cb.Oid = cbd.CashBank";
            //         $fromDetailTable2 = $fromDetailTable.$fromDetailTable2;

            //         //region INSERT JOURNAL BALEK WIP KE EXPENSE 1
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //             "Account" => "cbd.Account",                   "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",            "Rate" => "1",
            //             "TravelTransactionReport" => 'cb.TravelTransaction',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
            //             SELECT %s, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase)
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND cbd.AmountInvoiceBase != 0 AND cbd.GCRecord IS NULL AND cb.Type = 1
            //             GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //             p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate, cbd.Account";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //region INSERT JOURNAL BALEK WIP KE PAYMENT 1
                    
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //             "Account" => "co.ExpenseInProgress",                   "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",            "Rate" => "1",
            //             "TravelTransactionReport" => 'cb.TravelTransaction',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
            //             SELECT %s, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase)
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND cbd.AmountInvoiceBase != 0 AND cbd.GCRecord IS NULL AND cb.Type = 1
            //             GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //             p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //region INSERT JOURNAL BALEK WIP KE EXPENSE 2
            //         //endregion


            //         //region INSERT JOURNAL BALEK WIP KE INCOME 1
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //             "Account" => "cbd.Account",                   "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",            "Rate" => "1",
            //             "TravelTransactionReport" => 'cb.TravelTransaction',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
            //             SELECT %s, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase)
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND cbd.AmountInvoiceBase != 0 AND cbd.GCRecord IS NULL AND cb.Type = 0
            //             GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //             p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate, cbd.Account";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //region INSERT JOURNAL BALEK WIP KE INCOME 1
                    
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //             "Account" => "co.IncomeInProgress",                   "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",            "Rate" => "1",
            //             "TravelTransactionReport" => 'cb.TravelTransaction',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
            //             SELECT %s, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase), 0, SUM(cbd.AmountInvoiceBase)
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND cbd.AmountInvoiceBase != 0 AND cbd.GCRecord IS NULL AND cb.Type = 0
            //             GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //             p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //region INSERT JOURNAL BALEK WIP KE INCOME 2
            //         //endregion

            //         // //region INSERT JOURNAL TRAVELTRANSACTION
            //         // $fromDetailTable2 = "
            //         // LEFT OUTER JOIN pospointofsale pos ON d.Oid = pos.Oid";
            //         // $fromDetailTable2 = $fromDetailTable.$fromDetailTable2;
            //         // $arr = array_merge($arrDefault, [
            //         //     "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //         //     "Account" => "co.ItemSalesIncome",                   "BusinessPartner" => "NULL", 
            //         //     "Currency" => "p.Currency",            "Rate" => "IFNULL(p.Rate,1)",
            //         // ]);
            //         // $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
            //         //     SELECT %s, pos.TotalAmount, 0, 
            //         //     ROUND(pos.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)), 0, 
            //         //     ROUND(pos.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)) 
            //         //     FROM {$fromDetailTable2} WHERE {$whereClause} 
            //         //     AND pos.TotalAmount != 0 AND pos.GCRecord IS NULL
            //         //     GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //         //     p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate";
            //         // $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         // DB::insert($query); //region INSERT JOURNAL TRAVELTRANSACTION 1
            //         // //endregion

            //         //region INSERT JOURNAL TRAVELTRANSACTION
            //         $fromDetailTable2 = "
            //         LEFT OUTER JOIN pospointofsale pos ON d.Oid = pos.Oid";
            //         $fromDetailTable2 = $fromDetailTable.$fromDetailTable2;
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '))", 
            //             "Account" => "co.ItemSalesIncome",                   "BusinessPartner" => "NULL", 
            //             "Currency" => "p.Currency",            "Rate" => "IFNULL(p.Rate,1)",
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
            //             SELECT %s, pos.TotalAmount, 0, 
            //             ROUND(pos.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)), 0, 
            //             ROUND(pos.TotalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)) 
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND pos.TotalAmount != 0 AND pos.GCRecord IS NULL
            //             GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date, p.BusinessPartner, 
            //             p.Code, p.Date, co.Currency, cc.Decimal, bp.Name, p.Rate";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //region INSERT JOURNAL TRAVELTRANSACTION 1
            //         //endregion
                
            //         $fromDetailTable2 = "
            //         LEFT OUTER JOIN trvtransactioncommission com ON d.Oid = com.TravelTransaction";
            //         $fromDetailTable2 = $fromDetailTable.$fromDetailTable2;

            //         //region INSERT JOURNAL BALEK KOMISI PENDAPATAN
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': Commission ')", 
            //             "Account" => "'".$company->IncomeInProgress."'",       "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",         "Rate" => "1",
            //             "TravelTransactionReport" => 'd.Oid',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
            //             SELECT %s, 
            //             IF(SUM(com.AmountAcctIncome) < 0, com.AmountAcctIncome * -1, 0),
            //             IF(SUM(com.AmountAcctIncome) > 0, com.AmountAcctIncome, 0),
            //             IF(SUM(com.AmountAcctIncome) < 0, com.AmountAcctIncome * -1, 0),
            //             IF(SUM(com.AmountAcctIncome) > 0, com.AmountAcctIncome, 0),
            //             SUM(com.AmountAcctIncome)
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND com.AmountAcctIncome != 0 AND com.GCRecord IS NULL 
            //             GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, p.Rate, d.Oid, com.AccountIncome";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //INSERT JOURNAL 
            //         //endregion

            //         //region INSERT JOURNAL BALEK KOMISI PENDAPATAN
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': Commission ')", 
            //             "Account" => "com.AccountIncome",       "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",         "Rate" => "1",
            //             "TravelTransactionReport" => 'd.Oid',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
            //             SELECT %s, 
            //             IF(SUM(com.AmountAcctIncome) > 0, com.AmountAcctIncome, 0),
            //             IF(SUM(com.AmountAcctIncome) < 0, com.AmountAcctIncome * -1, 0),
            //             IF(SUM(com.AmountAcctIncome) > 0, com.AmountAcctIncome, 0),
            //             IF(SUM(com.AmountAcctIncome) < 0, com.AmountAcctIncome * -1, 0),
            //             SUM(com.AmountAcctIncome)
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND com.AmountAcctIncome != 0 AND com.GCRecord IS NULL 
            //             GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, p.Rate, d.Oid, com.AccountIncome";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //INSERT JOURNAL 
            //         //endregion

            //         //region INSERT JOURNAL BALEK KOMISI BIAYA
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': Commission ')", 
            //             "Account" => "'".$company->ExpenseInProgress."'",       "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",         "Rate" => "1",
            //             "TravelTransactionReport" => 'd.Oid',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
            //             SELECT %s, 
            //             IF(SUM(com.AmountAcctExpense) < 0, com.AmountAcctExpense * -1, 0),
            //             IF(SUM(com.AmountAcctExpense) > 0, com.AmountAcctExpense, 0),
            //             IF(SUM(com.AmountAcctExpense) < 0, com.AmountAcctExpense * -1, 0),
            //             IF(SUM(com.AmountAcctExpense) > 0, com.AmountAcctExpense, 0),
            //             SUM(com.AmountAcctExpense)
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND com.AmountAcctExpense != 0 AND com.GCRecord IS NULL 
            //             GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, p.Rate, d.Oid, com.AccountExpense";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //INSERT JOURNAL 
            //         //endregion

            //         //region INSERT JOURNAL BALEK KOMISI BIAYA 
            //         $arr = array_merge($arrDefault, [
            //             "Description" => "CONCAT(p.Code, ': Commission ')", 
            //             "Account" => "com.AccountExpense",       "BusinessPartner" => "NULL", 
            //             "Currency" => "co.Currency",         "Rate" => "1",
            //             "TravelTransactionReport" => 'd.Oid',
            //         ]);
            //         $query = "INSERT INTO accjournal (%s, DebetAmount, CreditAmount, DebetBase, CreditBase, TotalBase)
            //             SELECT %s, 
            //             IF(SUM(com.AmountAcctExpense) > 0, com.AmountAcctExpense, 0),
            //             IF(SUM(com.AmountAcctExpense) < 0, com.AmountAcctExpense * -1, 0),
            //             IF(SUM(com.AmountAcctExpense) > 0, com.AmountAcctExpense, 0),
            //             IF(SUM(com.AmountAcctExpense) < 0, com.AmountAcctExpense * -1, 0),
            //             SUM(com.AmountAcctExpense)
            //             FROM {$fromDetailTable2} WHERE {$whereClause} 
            //             AND com.AmountAcctExpense != 0 AND com.GCRecord IS NULL 
            //             GROUP BY p.company, jt.Oid, p.Oid, p.Code, p.Date, co.Currency, cc.Decimal, p.Rate, d.Oid, com.AccountExpense";
            //         $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //         DB::insert($query); //INSERT JOURNAL 
            //         //endregion
            //     }    
            // }

            //region INSERT JOURNAL ADDITIONAL
            if ($isBooking) $account = "'".$company->IncomeInProgress."'"; else $account = "p.AdditionalAccount";
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(p.AdditionalAmount,2), ')')))", 
                // "SalesInvoice" => "p.Oid",    "Code" => "p.Code",    "Date" => "p.Date", 
                "Account" => $account,    "BusinessPartner" => "NULL", 
                "Currency" => "p.Currency",            "Rate" => "IFNULL(p.Rate,1)",
                // "TravelTransactionReport" => "p.TravelTransaction",
            ]);
            $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
                SELECT %s, p.AdditionalAmount, 0, 
                ROUND(p.AdditionalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)), 0, 
                ROUND(p.AdditionalAmount * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)) 
                FROM {$fromParentTable} WHERE {$whereClause} 
                AND p.AdditionalAmount != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT JOURNAL ADDITIONAL
            //endregion

            //region INSERT JOURNAL DISKON
            if ($isBooking) $account = "'".$company->ExpenseInProgress."'"; else $account = "p.DiscountAccount";
            $arr = array_merge($arrDefault, [
                "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(p.DiscountAmount,2), ')')))", 
                "Account" => $account,      "BusinessPartner" => "NULL", 
                "Currency" => "p.Currency",            "Rate" => "IFNULL(p.Rate,1)",
                // "TravelTransactionReport" => "p.TravelTransaction",
            ]);
            $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
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
            if (isset($salesInvoice->CashBankPaymentPrepaid)) {
                $cashBankPrepaid = CashBank::findOrFail($salesInvoice->CashBankPaymentPrepaid);
                $prepaidAmount = $salesInvoice->PrepaidAmount;
                
                $arr = array_merge($arrDefault, [
                    "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT({$prepaidAmount},2), ')')))", 
                    "Account" => "'".$cashBankPrepaid->PrepaidAccount."'",           "BusinessPartner" => "p.BusinessPartner", 
                    "Currency" => "p.Currency",         "Rate" => "IFNULL(p.Rate,1)",
                ]);
                $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
                    SELECT %s, 0, {$prepaidAmount}, 0, 
                    ROUND(({$prepaidAmount}) * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)),
                    ROUND(({$prepaidAmount}) * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0))
                    FROM {$fromParentTable} WHERE {$whereClause} 
                    AND p.TotalAmount != 0";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //INSERT JOURNAL PREPAID
            } else if (isset($salesInvoice->AccountPrepaid)) {
                $prepaidAmount = $salesInvoice->PrepaidAmount;
                $accountPrepaid = Account::findOrFail($salesInvoice->AccountPrepaid);
                if ($salesInvoice->PrepaidAmount > $salesInvoice->PrepaidCurrencyAmount)
                    $prepaidRate = $salesInvoice->PrepaidAmount / $salesInvoice->PrepaidCurrencyAmount;
                else
                    $prepaidRate = $salesInvoice->PrepaidCurrencyAmount / $salesInvoice->PrepaidAmount;

                $arr = array_merge($arrDefault, [
                    "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT({$prepaidAmount},2), ')')))", 
                    "Account" => "'".$salesInvoice->AccountPrepaid."'",           "BusinessPartner" => "p.BusinessPartner", 
                    "Currency" => "'".$accountPrepaid->Currency."'",         "Rate" => "IFNULL(".$prepaidRate.",1)",
                ]);
                $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
                    SELECT %s, 0, {$salesInvoice->PrepaidCurrencyAmount}, 0, 
                    ROUND(({$salesInvoice->PrepaidAmount}) * IFNULL({$salesInvoice->Rate},1), IFNULL(cc.Decimal,0)),
                    ROUND(({$salesInvoice->PrepaidAmount}) * IFNULL({$salesInvoice->Rate},1), IFNULL(cc.Decimal,0))
                    FROM {$fromParentTable} WHERE {$whereClause} 
                    AND p.TotalAmount != 0";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //INSERT JOURNAL PREPAID
            }
            //endregion

            //region INSERT JOURNAL HUTANG            
            if ($salesInvoice->TotalAmount - $prepaidAmount != 0) {      
                $arr = array_merge($arrDefault, [
                    "Description" => "CONCAT(p.Code, ': ', bp.Name, DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(p.TotalAmount - {$prepaidAmount},2), ')')))", 
                    "Account" => "p.Account",           "BusinessPartner" => "p.BusinessPartner", 
                    "Currency" => "p.Currency",         "Rate" => "IFNULL(p.Rate,1)",
                    // "TravelTransactionReport" => "p.TravelTransaction",
                ]);
                $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
                    SELECT %s, 0, (p.TotalAmount - {$prepaidAmount}), 0, 
                    ROUND((p.TotalAmount - {$prepaidAmount}) * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0)),
                    ROUND((p.TotalAmount - {$prepaidAmount}) * IFNULL(p.Rate,1), IFNULL(cc.Decimal,0))
                    FROM {$fromParentTable} WHERE {$whereClause} 
                    AND p.TotalAmount != 0";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //INSERT JOURNAL HUTANG
            }
            //endregion

            // 20181215 Ser Kurs
            //region INSERT JOURNAL SELISH KURS
            // $differenceAmount = $prepaidDiffAmount + $salesInvoice->BaseDifference;
            // if ($differenceAmount != 0) {
            //     $arr = array_merge($arrDefault, [
            //         "Description" => "CONCAT(p.Code, ': {$company->BusinessPartnerAmountDifferenceObj->Name}', DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (KURS ', FORMAT(p.Rate,0), ')')))", 
            //         "Account" => "'{$company->BusinessPartnerAmountDifference}'", 
            //         "BusinessPartner" => "NULL", 
            //         "Currency" => "co.Currency",                   "Rate" => "IFNULL(p.Rate,1)",
            //     ]);
            //     $query = "INSERT INTO accjournal (%s, CreditAmount, DebetAmount, CreditBase, DebetBase, TotalBase)
            //         SELECT %s,
            //         IF({$differenceAmount} > 0 , {$differenceAmount}, 0), IF({$differenceAmount} > 0 , {$differenceAmount}, 0),
            //         IF({$differenceAmount} < 0 , {$differenceAmount} * -1, 0), IF({$differenceAmount} < 0 , {$differenceAmount} * -1, 0),
            //         IF({$differenceAmount} < 0, {$differenceAmount} * -1, {$differenceAmount})                
            //         FROM {$fromParentTable} WHERE {$whereClause} 
            //         AND p.TotalAmount != 0 AND {$differenceAmount} != 0";
            //     $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            //     DB::insert($query); //INSERT JOURNAL SELISH KURS
            // }
            //endregion

            SalesInvoice::where('Oid', $id)
            ->update([
                'Status' => Status::posted()->value('Oid'),
            ]);
        });
    }

    public function unpost($id)
    {
        DB::transaction(function() use ($id) {
            $salesInvoice = SalesInvoice::findOrFail($id);
            if ($this->isPeriodClosed($salesInvoice->Date)) {
                $this->throwPeriodIsClosedError($salesInvoice->Date);
            }
            $salesInvoice->Journals()->delete();
            SalesInvoice::where('Oid', $id)
            ->update([
                'Status' => Status::entry()->value('Oid'),
            ]);
        });
    }

    public function cancelled($id)
    {
        DB::transaction(function() use ($id) {
            $salesInvoice = SalesInvoice::findOrFail($id);
            if ($this->isPeriodClosed($salesInvoice->Date)) {
                $this->throwPeriodIsClosedError($salesInvoice->Date);
            }
            $salesInvoice->Journals()->delete();
            SalesInvoice::where('Oid', $id)
            ->update([
                'Status' => Status::cancelled()->value('Oid'),
            ]);
        });
    }
}