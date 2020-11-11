<?php

namespace App\Core\Accounting\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Core\Internal\Entities\Status;
use App\Core\Security\Entities\User;
use Illuminate\Support\Facades\Auth;
use App\Core\Internal\Entities\JournalType;
use App\Core\Accounting\Entities\Period;
use App\Core\Master\Entities\Currency;
use App\Core\Accounting\Services\SalesPOSService;

class ProcessJournalService 
{

    /** @var JournalService $this->journalService */
    protected $journalService;

    public function __construct(JournalService $journalService,SalesPOSService $salesPosService)
    {
        $this->journalService = $journalService;
        $this->salesPosService = $salesPosService;
    }

    /**
     * 
     * @param string $id
     * @return void
     */
    public function open($id)
    {
        DB::transaction(function() use ($id) {
            $user = Auth::user();
            $description = "Open by ".$user->UserName." ".Carbon::now()->format('d M H:i');

            $period = Period::findOrFail($id);
            Period::where('Oid', $id)
            ->update([
                'Status' => 0,
                'Description' => $description
            ]);
        });
    }

    /**
     * 
     * @param string $id
     * @return void
     */
    public function close($id)
    {
        $period = Period::findOrFail($id);
        if (!$this->journalService->checkUnpostedTransaction($period->DatePeriod)) throw new \Exception("There is unposted transaction");
        // if (!$this->journalService->checkUnposted($period->DatePeriod, 'accgeneraljournal')) throw new \Exception("There are unposted General Journal");
        // if (!$this->journalService->checkUnposted($period->DatePeriod, 'acccashbank')) throw new \Exception("There is unposted Cashbank");
        // if (!$this->journalService->checkUnposted($period->DatePeriod, 'accapinvoice')) throw new \Exception("There is unposted AP Invoice");
        // if (!$this->journalService->checkUnposted($period->DatePeriod, 'accapinvoice')) throw new \Exception("There is unposted AR Invoice");

        DB::transaction(function() use ($id) {
            $user = Auth::user();
            $description = "Close by ";
            $description .= $user->UserName;
            $description .= " ";
            $description .= Carbon::now()->format('d M H:i');

            Period::where('Oid', $id)
            ->update([
                'Status' => 1,
                'Description' => $description                
            ]);
        });
    }

    public function processPOS()
    {
        DB::transaction(function() {
            $query = "SELECT Oid,Code,Date FROM pospointofsale WHERE Status='21b3c6d2-21a2-4ee8-a11a-03ed16085123' AND GCRecord IS NULL ORDER BY Date";            
            $result = DB::select($query); 
            logger(count($result));
            for ($i = 0; $i < count($result); $i++) { //$row->Balance = Amount; $row->BalanceBase = Base Amount
                $row= $result[$i];
                logger($i.' '.$row->Oid);
                $this->salesPosService->post($row->Oid);
            }
        });
    }


    /**
     * 
     * @param string $id
     * @return void
     */
    public function process($id)
    {
        DB::transaction(function() use ($id) {
            $user = Auth::user();
            $period = Period::findOrFail($id); /** @var Period $period */
            $statusposted = Status::posted()->first();
            $statusentry = Status::entry()->first();
            $jtauto = JournalType::auto()->first();
            $jtstin = JournalType::stin()->first();
            $jtcogs = JournalType::cogs()->first();
            $jtpl = JournalType::where('Code','PL')->first();
            $jtopen = JournalType::open()->first();

            // if (!$this->journalService->checkUnpostedTransaction($period->DatePeriod, $user->Company)) throw new \Exception("There is unposted transaction");;
            
            // 1. YANG PLUS DITAMBAHKAN DULU NILAI NYA KE COST, DAN NANTI AKAN DIHITUNG
            // Update semua pembelian utk cost nya, kecuali transfer (atau auto)
            $query = "UPDATE trdtransactionstock 
                LEFT OUTER JOIN sysjournaltype jt ON JournalType = jt.Oid
                SET StockCost = trdtransactionstock.StockAmount
                WHERE DATE_FORMAT(trdtransactionstock.Date, '%Y%m') = '{$period->EndDate->format('Ym')}'
                AND trdtransactionstock.StockQuantity > 0
                AND jt.Code NOT IN ('Auto', 'STIN', 'STOUT')";
            DB::update($query);

            // 2. MASUKKAN NILAI TRANSFER IN
            // Update hpp khusus utk transfer in
            $query = "UPDATE trdtransactionstock 
                LEFT OUTER JOIN (
                    SELECT stk.Item, stk.Warehouse, SUM(IFNULL(stk.Quantity,0)) AS TotalQuantity, SUM(IFNULL(stk.Cost,0)) AS TotalCost
                    FROM (
                        SELECT stk.Item, stk.Warehouse, 'Start' AS Type, 
                        SUM(IFNULL(stk.StockQuantity,0)) AS Quantity, SUM(IFNULL(stk.StockQuantity,0) * IFNULL(stk.StockCost,0)) AS Cost
                        FROM trdtransactionstock stk
                        LEFT OUTER JOIN sysjournaltype jt ON stk.JournalType = jt.Oid
                        WHERE jt.Code !='Auto' AND DATE_FORMAT(stk.Date, '%Y%m') < '{$period->StartDate->format('Ym')}'
                        GROUP BY stk.Warehouse, stk.Item
                        UNION ALL
                        SELECT stk.Item, stk.Warehouse, 'Stock' AS Type,
                        SUM(IFNULL(stk.StockQuantity,0)) AS Quantity, SUM(IFNULL(stk.StockQuantity,0) * IFNULL(stk.StockCost,0)) AS Cost
                        FROM trdtransactionstock stk
                        LEFT OUTER JOIN sysjournaltype jt ON stk.JournalType = jt.Oid
                        WHERE stk.StockQuantity > 0 AND jt.Code !='Auto' AND jt.Code != 'STIN' AND DATE_FORMAT(stk.Date, '%Y%m') = '{$period->StartDate->format('Ym')}'
                        GROUP BY stk.Warehouse, stk.Item
                    ) AS stk
                    GROUP BY stk.Item, stk.Warehouse
                ) AS Calc ON trdtransactionstock.Item = Calc.Item AND trdtransactionstock.Warehouse = Calc.Warehouse
                LEFT OUTER JOIN sysjournaltype jt ON JournalType = jt.Oid
                SET StockCost = CASE WHEN IFNULL(Calc.TotalQuantity,0) = 0 THEN IFNULL(Calc.TotalCost,0) ELSE IFNULL(Calc.TotalCost,0) / IFNULL(Calc.TotalQuantity,0) END
                WHERE DATE_FORMAT(trdtransactionstock.Date, '%Y%m') = '{$period->EndDate->format('Ym')}'
                AND trdtransactionstock.StockQuantity > 0
                AND jt.Code = 'STIN'";
            DB::update($query); //UPDATE COST PRICE

            // 3. HITUNG HPP
            // Update hpp utk semua stok < 0 dari hpp yg sdh dihitung
            $query = "UPDATE trdtransactionstock 
                LEFT OUTER JOIN (
                    SELECT stk.Item, stk.Warehouse, SUM(IFNULL(stk.Quantity,0)) AS TotalQuantity, SUM(IFNULL(stk.Cost,0)) AS TotalCost
                    FROM (
                        SELECT stk.Item, stk.Warehouse, 'Start' AS Type, 
                        SUM(IFNULL(stk.StockQuantity,0)) AS Quantity, SUM(IFNULL(stk.StockQuantity,0) * IFNULL(stk.StockCost,0)) AS Cost
                        FROM trdtransactionstock stk
                        LEFT OUTER JOIN sysjournaltype jt ON stk.JournalType = jt.Oid
                        WHERE jt.Code !='Auto' AND DATE_FORMAT(stk.Date, '%Y%m') < '{$period->StartDate->format('Ym')}'
                        GROUP BY stk.Warehouse, stk.Item
                        UNION ALL
                        SELECT stk.Item, stk.Warehouse, 'Stock' AS Type,
                        SUM(IFNULL(stk.StockQuantity,0)) AS Quantity, SUM(IFNULL(stk.StockQuantity,0) * IFNULL(stk.StockCost,0)) AS Cost
                        FROM trdtransactionstock stk
                        LEFT OUTER JOIN sysjournaltype jt ON stk.JournalType = jt.Oid
                        WHERE stk.StockQuantity > 0 AND jt.Code !='Auto' AND DATE_FORMAT(stk.Date, '%Y%m') = '{$period->StartDate->format('Ym')}'
                        GROUP BY stk.Warehouse, stk.Item
                    ) AS stk
                    GROUP BY stk.Item, stk.Warehouse
                ) AS Calc ON trdtransactionstock.Item = Calc.Item AND trdtransactionstock.Warehouse = Calc.Warehouse
                LEFT OUTER JOIN sysjournaltype jt ON JournalType = jt.Oid
                SET StockCost = CASE WHEN IFNULL(Calc.TotalQuantity,0) = 0 THEN IFNULL(Calc.TotalCost,0) ELSE IFNULL(Calc.TotalCost,0) / IFNULL(Calc.TotalQuantity,0) END
                WHERE DATE_FORMAT(trdtransactionstock.Date, '%Y%m') = '{$period->EndDate->format('Ym')}'
                AND trdtransactionstock.StockQuantity < 0
                AND jt.Code != 'Auto'";
            DB::update($query); //UPDATE COST PRICE

            // 4. HITUNG SALDO AWAL
            $query = "DELETE FROM trdtransactionstock WHERE DATE_FORMAT(Date, '%Y%m') = {$period->DatePeriod} 
                AND Company = '{$user->Company}' AND JournalType = '{$jtauto->Oid}'";
            DB::delete($query); //QUERY DELETE AUTO JOURNAL
            $query = "INSERT INTO trdtransactionstock 
                (Oid,Company,JournalType,Code,Date,Warehouse,Item,Quantity,Price,StockQuantity,StockAmount,StockCost,Currency,Rate,Status)
                SELECT UUID(), co.Oid, '{$jtauto->Oid}', '{$period->DatePeriod}', '{$period->EndDate->toDateString()}', s.Warehouse, s.Item, 
                SUM(IFNULL(s.StockQuantity,0)), SUM(IFNULL(s.StockCost,0) * IFNULL(s.StockQuantity,0))/SUM(IFNULL(s.StockQuantity,0)), 
                SUM(IFNULL(s.StockQuantity,0)), SUM(IFNULL(s.StockCost,0) * IFNULL(s.StockQuantity,0))/SUM(IFNULL(s.StockQuantity,0)), 
                SUM(IFNULL(s.StockCost,0) * IFNULL(s.StockQuantity,0))/SUM(IFNULL(s.StockQuantity,0)), co.Currency, 1, sp.Oid
                FROM trdtransactionstock s
                LEFT OUTER JOIN sysjournaltype jt ON s.JournalType = jt.Oid
                LEFT OUTER JOIN company co ON s.Company = co.Oid
                LEFT OUTER JOIN sysstatus sp ON sp.Code = 'posted'
                WHERE DATE_FORMAT(s.Date, '%Y%m') = '{$period->EndDate->format('Ym')}'
                AND jt.Code != 'Auto'
                GROUP BY s.Item, s.Warehouse";
            DB::insert($query); //UPDATE COST PRICE

            //region QUERY DELETE
            $query = "DELETE FROM accjournal
                WHERE DATE_FORMAT(Date, '%Y%m') = {$period->DatePeriod} 
                AND Company = '{$user->Company}' 
                AND JournalType = '{$jtauto->Oid}'";
            DB::delete($query); //QUERY DELETE AUTO JOURNAL
            $query = "DELETE FROM accjournal
                WHERE DATE_FORMAT(Date, '%Y%m') = {$period->DatePeriod} 
                AND Company = '{$user->Company}' 
                AND JournalType = '{$jtpl->Oid}'";
            DB::delete($query); //QUERY DELETE JOURNAL PL
            // WHERE Date = '{$period->NextPeriod->toDateString()}' 
            $query = "DELETE FROM accjournal
                WHERE DATE_FORMAT(Date, '%Y%m') = {$period->DatePeriod} 
                AND Company = '{$user->Company}' 
                AND JournalType = '{$jtopen->Oid}'";
            DB::delete($query); //QUERY DELETE JOURNAL OPENING BALANCE
            $query = "DELETE FROM accjournal
                WHERE DATE_FORMAT(Date, '%Y%m') = {$period->DatePeriod} 
                AND Company = '{$user->Company}' 
                AND JournalType = '{$jtcogs->Oid}'";
            DB::delete($query); //QUERY DELETE JOURNAL COGS
            $query = "DELETE FROM accjournalperiod 
                WHERE Period = {$period->DatePeriod} 
                AND Company = '{$user->Company}'";
            DB::delete($query); //QUERY DELETE JOURNAL SUMMARY (PERIOD)
            //endregion

            
            // //BEGIN OF : INSERT JOURNAL HPP SALES INVOICE---------------------------------------------------------------- 
            $arrDefault = [
                "Oid" => "UUID() AS Oid",             "Company" => "p.Company",         "CreatedAt" => "NOW() AS CreatedAt",        "Status" => "s.Oid AS Status", 
                "SalesInvoice" => "p.Oid AS SalesInvoice",     "Source" => "'Sales-Invoice' AS Source",    "Code" => "p.Code",            "Date" => "p.Date", 
                "Description" => "CONCAT(p.Code, ': ', DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(SUM(p.TotalAmount),2), ')')))", 
                "BusinessPartner" => "null AS BusinessPartner",  "Currency" => "co.Currency",       "Rate" => "1", "JournalType" => "jt.Oid AS JournalType", 
            ];
            $fromDetailTable =  "trdtransactionstock d
                LEFT OUTER JOIN trdsalesinvoice p ON p.Oid = d.SalesInvoice
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.SalesProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'COGS'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency";
            $whereClause = "WHERE p.Company = '{$user->Company}'
                            AND p.GCRecord IS NULL
                            AND d.StockCost != 0 AND d.GCRecord IS NULL
                            AND DATE_FORMAT(p.Date, '%Y%m') = '{$period->EndDate->format('Ym')}'
                            AND d.SalesInvoice IS NOT NULL";
            $arr = array_merge($arrDefault, [
                "Account" => "IFNULL(iag.PurchaseExpense, co.ItemPurchaseExpense) AS Account",
                "DebetAmount" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))", "DebetBase" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))", 
                "CreditAmount" => "0",       "CreditBase" => "null", 
                "TotalBase" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))",
            ]);
            
            $query = "INSERT INTO accjournal (%s) 
                SELECT %s
                FROM {$fromDetailTable} WHERECLAUSE
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date,
                p.Code, p.Date, p.Currency, co.Currency, cc.Decimal, p.Rate, iag.PurchaseExpense";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            $query = str_replace("WHERECLAUSE", $whereClause, $query);
            logger($query);
            DB::insert($query);
            $arr = array_merge($arrDefault, [
                "Account" => "IFNULL(iag.StockAccount, co.ItemStock) AS Account",
                "CreditAmount" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))", "CreditBase" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))", 
                "DebetAmount" => "0",       "DebetBase" => "null", 
                "TotalBase" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))",
            ]);
            $query = "INSERT INTO accjournal (%s) 
                SELECT %s
                FROM {$fromDetailTable} WHERECLAUSE
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date,
                p.Code, p.Date, p.Currency, co.Currency, cc.Decimal, p.Rate, iag.StockAccount";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            $query = str_replace("WHERECLAUSE", $whereClause, $query);
            logger($query);
            DB::insert($query);
            // //ENDING OF : INSERT JOURNAL HPP SALES INVOICE---------------------------------------------------------------- 


            
            // //BEGIN OF : INSERT JOURNAL HPP POS---------------------------------------------------------------- 
            $arrDefault = [
                "Oid" => "UUID() AS Oid",             "Company" => "p.Company",         "CreatedAt" => "NOW() AS CreatedAt",        "Status" => "s.Oid AS Status", 
                "PointOfSale" => "p.Oid AS PointOfSale",     "Source" => "'POS' AS Source",    "Code" => "p.Code",            "Date" => "p.Date", 
                "Description" => "CONCAT(p.Code, ': ', DATE_FORMAT(p.Date, ' %d/%b '), IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.RateAmount,0), ' x ', FORMAT(SUM(p.TotalAmount),2), ')'))) AS Description", 
                "BusinessPartner" => "null AS BusinessPartner",  "Currency" => "co.Currency",       "Rate" => "1", "JournalType" => "jt.Oid AS JournalType", 
            ];
            $fromDetailTable =  "trdtransactionstock d
                LEFT OUTER JOIN pospointofsale p ON p.Oid = d.PointOfSale
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.SalesProduction
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'COGS'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency ac ON ac.Oid = a.Currency";
            $whereClause = "WHERE p.Company = '{$user->Company}'
                            AND p.GCRecord IS NULL
                            AND d.StockCost != 0 AND d.GCRecord IS NULL
                            AND DATE_FORMAT(p.Date, '%Y%m') = '{$period->EndDate->format('Ym')}'
                            AND d.PointOfSale IS NOT NULL";
            $arr = array_merge($arrDefault, [
                "Account" => "IFNULL(iag.PurchaseExpense, co.ItemPurchaseExpense) AS Account",
                "DebetAmount" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))", "DebetBase" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))", 
                "CreditAmount" => "0",       "CreditBase" => "null", 
                "TotalBase" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))",
            ]);
            
            $query = "INSERT INTO accjournal (%s) 
                SELECT %s
                FROM {$fromDetailTable} WHERECLAUSE
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date,
                p.Code, p.Date, p.Currency, co.Currency, cc.Decimal, p.RateAmount, iag.PurchaseExpense";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            $query = str_replace("WHERECLAUSE", $whereClause, $query);
            logger($query);
            DB::insert($query);
            $arr = array_merge($arrDefault, [
                "Account" => "IFNULL(iag.StockAccount, co.ItemStock) AS Account",
                "CreditAmount" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))", "CreditBase" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))", 
                "DebetAmount" => "0",       "DebetBase" => "null", 
                "TotalBase" => "SUM(ROUND(IFNULL(d.Quantity,0) * IFNULL(d.StockCost,0),cc.Decimal))",
            ]);
            $query = "INSERT INTO accjournal (%s) SELECT %s
                FROM {$fromDetailTable} WHERECLAUSE
                GROUP BY p.company, jt.Oid, s.Oid, p.Oid, p.Code, p.Date,
                p.Code, p.Date, p.Currency, co.Currency, cc.Decimal, p.RateAmount, iag.StockAccount";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            $query = str_replace("WHERECLAUSE", $whereClause, $query);
            logger($query);
            DB::insert($query);
            // //ENDING OF : INSERT JOURNAL HPP POS---------------------------------------------------------------- 
            

            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [ 
                "Oid" => "UUID()",                      "Company" => "'{$user->Company}'", "CreatedAt" => "NOW()",
                "Source" => "'Process'",                "Status" => "'{$statusposted->Oid}'",                 
                "Code" => "'{$period->DatePeriod}'",    "Date" => "'{$period->EndDate->toDateString()}'", 
            ];
            $fromTable =  "accjournal p
                LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = p.JournalType
                LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                LEFT OUTER JOIN accaccount a ON a.Oid = p.Account
                LEFT OUTER JOIN sysaccounttype at ON at.Oid = a.AccountType
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner";
            $whereClause = "AND p.Company = '{$user->Company}'
                            AND p.GCRecord IS NULL";
            // $dateInitial = Carbon::parse($travelPurchaseInvoice->Date)->format("d/M");
            //endregion


            //region QUERY INSERT SELISIH SEMUA AKUN BUKAN RUPIAH CASH/BANK
            $query = "SELECT p.Account as Account, a.Name AS accName, p.BusinessPartner as BusinessPartner, bp.Name AS BPName, a.Currency as Currency,
                    SUM(IFNULL(p.DebetAmount,0) - IFNULL(p.CreditAmount,0)) AS Balance,
                    SUM(IFNULL(p.DebetBase,0) - IFNULL(p.CreditBase,0)) AS BalanceBase
                FROM {$fromTable}
                WHERE jt.Code != 'OPEN'
                    AND s.Code = 'Posted'
                    AND a.Currency != co.Currency
                    AND DATE_FORMAT(p.Date, '%Y%m') <= '{$period->EndDate->format('Ym')}' 
                    AND p.BusinessPartner IS NULL
                    AND p.GCRecord IS NULL
                    {$whereClause}
                GROUP BY p.Account, p.BusinessPartner, a.Currency, bp.Name
                HAVING SUM(p.DebetBase - p.CreditBase) != 0";
            $result = DB::select($query); //QUERY SELECT SEMUA AKUN BUKAN RUPIAH
            $amtTotalDiff = 0;
            for ($i = 0; $i < count($result); $i++) { //$row->Balance = Amount; $row->BalanceBase = Base Amount
                $row= $result[$i];                
                $currency = Currency::findOrFail($row->Currency); /** @var Currency $currency */
                $currencyRate = $currency->getRate($period->EndDate);
                $rateMonth  = $currencyRate ? $currencyRate->MidRate : 1;
                // $amtNowBase  = $user->CompanyObj->CurrencyObj->round($row->Balance * $rateMonth);
                $amtNowBase  = $currency->toBaseAmount($row->Balance, $rateMonth);
                $amtDiff  = $amtNowBase - $row->BalanceBase;
                // logger($row->accName.' '.$row->BPName.' '.$row->Balance.' x '.$rateMonth.' = '.$amtNowBase.' - '.$row->BalanceBase.' = '.$amtDiff);
                if (isset($row->BusinessPartner)) $businessPartner = "'".$row->BusinessPartner."'"; else $businessPartner = "NULL";
                if ($amtDiff != 0) {                    
                    $arr = array_merge($arrDefault, [ "JournalType" => "'{$jtauto->Oid}'", 
                        "Description" => "'Selisih Kurs Periode {$period->DatePeriod} (".number_format($rateMonth,2).") {$row->BPName}'",
                        "Account" => "'{$row->Account}'",       "BusinessPartner" => $businessPartner, 
                        "Currency" => "'{$currency->Oid}'",     "Rate" => $rateMonth,
                        "DebetAmount" => 0,                     "DebetBase" => ($amtDiff > 0 ? $amtDiff : 0), 
                        "CreditAmount" => 0,                    "CreditBase" => ($amtDiff < 0 ? $amtDiff * -1 : 0), 
                        "TotalBase" => ($amtDiff < 0 ? $amtDiff * -1 : $amtDiff), 
                    ]);
                    $query = "INSERT INTO accjournal (%s) VALUES (%s)";
                    $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                    DB::insert($query); //QUERY INSERT SELISIH KURS SEMUA AKUN BUKAN RUPIAH                   
                    $amtTotalDiff += $amtDiff;
                }
            }
            //region QUERY INSERT SELISIH KURS LAWAN
            if ($amtTotalDiff != 0) {                
                $arr = array_merge($arrDefault, [ 
                    "JournalType" => "'{$jtauto->Oid}'", "BusinessPartner" => "NULL",
                    "Account" => "'".($amtTotalDiff < 0 ? $user->CompanyObj->CashBankExchRateLoss : $user->CompanyObj->CashBankExchRateGain)."'",
                    "Description" => "'Selisih Kurs Periode {$period->DatePeriod} (".number_format($rateMonth,2).")'",
                    "Currency" => "'{$user->CompanyObj->Currency}'",     "Rate" => 1,
                    "DebetAmount" => ($amtTotalDiff < 0 ? $amtTotalDiff * -1 : 0), 
                    "DebetBase" => ($amtTotalDiff < 0 ? $amtTotalDiff * -1 : 0), 
                    "CreditAmount" => ($amtTotalDiff > 0 ? $amtTotalDiff : 0), 
                    "CreditBase" => ($amtTotalDiff > 0 ? $amtTotalDiff : 0), 
                    "TotalBase" => ($amtTotalDiff < 0 ? $amtTotalDiff * -1 : $amtTotalDiff), 
                ]);
                $query = "INSERT INTO accjournal (%s) VALUES (%s)";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //QUERY INSERT SELISIH KURS LAWAN                
            }
            // //endregion

            //region QUERY INSERT SELISIH SEMUA AKUN BUKAN RUPIAH AR/AP
            $query = "SELECT p.Account as Account, a.Name AS accName, p.BusinessPartner as BusinessPartner, bp.Name AS BPName, a.Currency as Currency,
                    SUM(p.DebetAmount - p.CreditAmount) AS Balance,
                    SUM(p.DebetBase - p.CreditBase) AS BalanceBase
                FROM {$fromTable}
                WHERE jt.Code != 'OPEN'
                    AND s.Code = 'Posted'
                    AND a.Currency != co.Currency
                    AND DATE_FORMAT(p.Date, '%Y%m') <= '{$period->EndDate->format('Ym')}' 
                    AND p.GCRecord IS NULL
                    -- AND p.BusinessPartner IS NOT NULL --- MEMPERBOLEHKAN BUSINESSPARTNER TDK ADA NILAI
                    {$whereClause}
                GROUP BY p.Account, p.BusinessPartner, a.Currency, bp.Name
                HAVING SUM(p.DebetBase - p.CreditBase) != 0";
            $result = DB::select($query); //QUERY SELECT SEMUA AKUN BUKAN RUPIAH
            $amtTotalDiff = 0;
            for ($i = 0; $i < count($result); $i++) { //$row->Balance = Amount; $row->BalanceBase = Base Amount
                $row= $result[$i];                
                $currency = Currency::findOrFail($row->Currency); /** @var Currency $currency */
                $currencyRate = $currency->getRate($period->EndDate);
                $rateMonth  = $currencyRate ? $currencyRate->MidRate : 1;
                // $amtNowBase  = $user->CompanyObj->CurrencyObj->round($row->Balance * $rateMonth);
                $amtNowBase  = $currency->toBaseAmount($row->Balance, $rateMonth);
                $amtDiff  = $amtNowBase - $row->BalanceBase;
                // logger($row->accName.' '.$row->BPName.' '.$row->Balance.' x '.$rateMonth.' = '.$amtNowBase.' - '.$row->BalanceBase.' = '.$amtDiff);
                if (isset($row->BusinessPartner)) $businessPartner = "'".$row->BusinessPartner."'"; else $businessPartner = "NULL";
                if ($amtDiff != 0) {                    
                    $arr = array_merge($arrDefault, [ "JournalType" => "'{$jtauto->Oid}'", 
                        "Description" => "'Selisih Kurs Periode {$period->DatePeriod} (".number_format($rateMonth,2).") {$row->BPName}'",
                        "Account" => "'{$row->Account}'",       "BusinessPartner" => $businessPartner, 
                        "Currency" => "'{$currency->Oid}'",     "Rate" => $rateMonth,
                        "DebetAmount" => 0,                     "DebetBase" => ($amtDiff > 0 ? $amtDiff : 0), 
                        "CreditAmount" => 0,                    "CreditBase" => ($amtDiff < 0 ? $amtDiff * -1 : 0), 
                        "TotalBase" => ($amtDiff < 0 ? $amtDiff * -1 : $amtDiff), 
                    ]);
                    $query = "INSERT INTO accjournal (%s) VALUES (%s)";
                    $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                    DB::insert($query); //QUERY INSERT SELISIH KURS SEMUA AKUN BUKAN RUPIAH                   
                    $amtTotalDiff += $amtDiff;
                }
            }
            //region QUERY INSERT SELISIH KURS LAWAN
            if ($amtTotalDiff != 0) {                
                $arr = array_merge($arrDefault, [ 
                    "JournalType" => "'{$jtauto->Oid}'", "BusinessPartner" => "NULL",
                    "Account" => "'".($amtTotalDiff < 0 ? $user->CompanyObj->ARAPExchRateLoss : $user->CompanyObj->ARAPExchRateGain)."'",
                    "Description" => "'Selisih Kurs Periode {$period->DatePeriod} (".number_format($rateMonth,2).")'",
                    "Currency" => "'{$user->CompanyObj->Currency}'",     "Rate" => 1,
                    "DebetAmount" => ($amtTotalDiff < 0 ? $amtTotalDiff * -1 : 0), 
                    "DebetBase" => ($amtTotalDiff < 0 ? $amtTotalDiff * -1 : 0), 
                    "CreditAmount" => ($amtTotalDiff > 0 ? $amtTotalDiff : 0), 
                    "CreditBase" => ($amtTotalDiff > 0 ? $amtTotalDiff : 0), 
                    "TotalBase" => ($amtTotalDiff < 0 ? $amtTotalDiff * -1 : $amtTotalDiff), 
                ]);
                $query = "INSERT INTO accjournal (%s) VALUES (%s)";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //QUERY INSERT SELISIH KURS LAWAN                
            }
            // //endregion


            
            //region QUERY INSERT JOURNAL PROFIT LOSS
            // $arr = array_merge($arrDefault, [ 
            //     "JournalType" => "'{$jtpl->Oid}'", "BusinessPartner" => "NULL",                     
            //     "Account" => "'{$user->CompanyObj->AccountProfitLoss}'",       
            //     "Description" => "'Laba Rugi bulan Periode {$period->DatePeriod}'",
            //     "Currency" => "'{$user->CompanyObj->Currency}'", "Rate" => 1,
            //     "DebetAmount" => "IF(SUM(p.CreditBase - p.DebetBase) < 0, SUM(p.CreditBase - p.DebetBase) * -1, 0)", 
            //     "DebetBase" => "IF(SUM(p.CreditBase - p.DebetBase) < 0, SUM(p.CreditBase - p.DebetBase) * -1, 0)", 
            //     "CreditAmount" => "IF(SUM(p.CreditBase - p.DebetBase) > 0, SUM(p.CreditBase - p.DebetBase)*-1, 0)", 
            //     "CreditBase" => "IF(SUM(p.CreditBase - p.DebetBase) > 0, SUM(p.CreditBase - p.DebetBase)*-1, 0)",
            //     "TotalBase" => "SUM(p.CreditBase - p.DebetBase)",
            // ]); 
            // UPDATE BY SER 20190503 tanya ke sylvia menjelaskan kalo di PL adalah laba maka seharusnya nilai nya plus
            $arr = array_merge($arrDefault, [ 
                "JournalType" => "'{$jtpl->Oid}'", "BusinessPartner" => "NULL",                     
                "Account" => "'{$user->CompanyObj->AccountProfitLoss}'",       
                "Description" => "'Laba Rugi bulan Periode {$period->DatePeriod}'",
                "Currency" => "'{$user->CompanyObj->Currency}'", "Rate" => 1,
                "DebetAmount" => "IF(SUM(p.CreditBase - p.DebetBase) < 0, SUM(p.CreditBase - p.DebetBase)*-1, 0)", 
                "DebetBase" => "IF(SUM(p.CreditBase - p.DebetBase) < 0, SUM(p.CreditBase - p.DebetBase)*-1, 0)", 
                "CreditAmount" => "IF(SUM(p.CreditBase - p.DebetBase) > 0, SUM(p.CreditBase - p.DebetBase), 0)", 
                "CreditBase" => "IF(SUM(p.CreditBase - p.DebetBase) > 0, SUM(p.CreditBase - p.DebetBase), 0)",
                "TotalBase" => "SUM(p.CreditBase - p.DebetBase)",
            ]);
            $query = "INSERT INTO accjournal (%s)
                SELECT %s
                FROM {$fromTable}
                WHERE jt.Code != 'OPEN'
                AND p.Status != '{$statusentry->Oid}' 
                AND LENGTH(at.ProfitLossGroup) > 0
                AND DATE_FORMAT(p.Date, '%s') = {$period->DatePeriod}
                AND p.GCRecord IS NULL
                {$whereClause}
                HAVING SUM(IFNULL(p.DebetBase,0) - IFNULL(p.CreditBase,0)) != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr), '%Y%m');
            DB::insert($query); //QUERY INSERT JOURNAL PROFIT LOSS
            //endregion

            //region QUERY INSERT JOURNAL OPENING BALANCE
            $arr = array_merge($arrDefault, [ 
                "JournalType" => "'{$jtopen->Oid}'", "BusinessPartner" => "p.BusinessPartner",                     
                "Account" => "p.Account",       
                "Description" => "'Saldo awal dari Periode {$period->DatePeriod}'",
                "Currency" => "p.Currency", 
                "Rate" => "IFNULL(SUM(DebetBase - CreditBase) / SUM(DebetAmount - CreditAmount), 1)",
                "DebetAmount" => "IF(SUM(DebetAmount - CreditAmount) > 0, SUM(DebetAmount - CreditAmount), 0)", 
                "DebetBase" => "IF(SUM(DebetBase - CreditBase) > 0, SUM(DebetBase - CreditBase), 0)", 
                "CreditAmount" => "IF(SUM(DebetAmount - CreditAmount) < 0, SUM(DebetAmount - CreditAmount)*-1, 0)", 
                "CreditBase" => "IF(SUM(DebetBase - CreditBase) < 0, SUM(DebetBase - CreditBase)*-1, 0)",
                "TotalBase" => "SUM(DebetBase - CreditBase)", 
            ]);
            $query = "INSERT INTO accjournal (%s)
                SELECT %s
                FROM {$fromTable}
                WHERE jt.Code != 'OPEN'
                AND p.Status != '{$statusentry->Oid}' 
                AND at.Code IN ('CASH', 'BANK', 'AR', 'AP', 'PDP', 'SDP')
                AND DATE_FORMAT(p.Date, '%s') = {$period->DatePeriod}
                AND p.GCRecord IS NULL
                {$whereClause}
                GROUP BY p.Account, p.BusinessPartner, p.Company
                HAVING SUM(IFNULL(DebetBase,0) + IFNULL(CreditBase,0) + IFNULL(DebetAmount,0) + IFNULL(CreditAmount,0)) != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr), '%Y%m');
            DB::insert($query); //QUERY INSERT JOURNAL OPENING BALANCE
            //endregion

            //region QUERY INSERT JOURNAL SUMMARY
            $arr = array_merge($arrDefault, [ 
                "JournalType" => "'{$jtopen->Oid}'", "BusinessPartner" => "p.BusinessPartner",                     
                "Account" => "p.Account",       
                "Description" => "'Saldo awal dari Periode {$period->DatePeriod}'",
                "Currency" => "co.Currency", 
                "Rate" => 1,
                "DebetAmount" => "IF(SUM(DebetBase - CreditBase) > 0, SUM(DebetBase - CreditBase), 0)", 
                "DebetBase" => "IF(SUM(DebetBase - CreditBase) > 0, SUM(DebetBase - CreditBase), 0)", 
                "CreditAmount" => "IF(SUM(DebetBase - CreditBase) < 0, SUM(DebetBase - CreditBase)*-1, 0)",
                "CreditBase" => "IF(SUM(DebetBase - CreditBase) < 0, SUM(DebetBase - CreditBase)*-1, 0)",
                "TotalBase" => "SUM(DebetBase - CreditBase)", 
            ]);
            $query = "INSERT INTO accjournal (%s)
                SELECT %s
                FROM {$fromTable}
                WHERE jt.Code != 'OPEN'
                AND p.Status != '{$statusentry->Oid}' 
                -- AND p.Status != '{$statusposted->Oid}'
                AND at.Code NOT IN ('CASH', 'BANK', 'AR', 'AP', 'PDP', 'SDP')
                AND DATE_FORMAT(p.Date, '%s') = {$period->DatePeriod}
                AND p.GCRecord IS NULL
                {$whereClause}
                GROUP BY p.Account, p.BusinessPartner, p.Company
                HAVING SUM(IFNULL(DebetBase,0) + IFNULL(CreditBase,0) + IFNULL(DebetAmount,0) + IFNULL(CreditAmount,0)) != 0";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr), '%Y%m');
            DB::insert($query); //QUERY INSERT JOURNAL SUMMARY
            //endregion

            $description = "Processed by {$user->UserName} ".Carbon::now()->addHours($user->CompanyObj->Timezone)->format('d M H:i:s');
            Period::where('Oid', $id)
            ->update([
                'Processed' => 1,
                // 'Status' => 1,
                'Description' => $description                
            ]);
        });
    }
}