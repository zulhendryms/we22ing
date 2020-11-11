<?php

namespace App\Core\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Internal\Entities\JournalType;
use App\Core\Internal\Entities\Status;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\Journal;
use App\Core\Security\Entities\User;
use App\Core\Accounting\Entities\Account;
use Illuminate\Support\Facades\Auth;

class CashBankService extends JournalObjectService
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
            $status = Status::posted()->first();                         
            $cashBank = CashBank::with(['BusinessPartnerObj','AccountObj','AccountObj.CurrencyObj'])->where('Oid',$id)->first();
            $company = $cashBank->CompanyObj;
            if ($cashBank->AdditionalAmount > 0 && !isset($cashBank->AdditionalAccount) && $cashBank->Type != 4) throw new \Exception("Additional Account must be filled");
            if ($cashBank->DiscountAmount > 0 && !isset($cashBank->DiscountAccount)) throw new \Exception("Discount Account must be filled");
            if ($this->isPeriodClosed($cashBank->Date)) {
                $this->throwPeriodIsClosedError($cashBank->Date);
            }
            $cashBank->Journals()->delete();
            
            //PINDAHAN DARI BAWAH, PENGAMBILAN KURS SBLMNYA HRS TERJADI SBLM ADA JURNAL APAPUN< BIAR KURS TERAKHIR YG DIANBIL BISA AKURAT 20180904
            $curFrRateBefore = $this->journalService->getRate($cashBank->Date, $cashBank->Account);

            $prepaidRateBefore = 0;
            if (isset($cashBank->PrepaidAccount)) $prepaidRateBefore = $this->journalService->getRate($cashBank->Date, $cashBank->PrepaidAccount);                    
            
            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = ''; $cashbankOutDiffAmount = 0;
            $arrDefault = [
                "Oid" => "UUID()",                      "Company" => "p.Company", "CreatedAt" => "NOW()",
                "JournalType" => "jt.Oid",              "Status" => "s.Oid", 
                "CashBank" => "p.Oid",                  "Source" => "'{$cashBank->TypeName}'",
                "Code" => "p.Code",                     "Date" => "p.Date", 
                "Note" => "p.Note",
            ];
            $fromParentTable =  "acccashbank p
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'Cash'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency bc ON bc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN accaccount a ON a.Oid = p.Account
                LEFT OUTER JOIN accaccount ta ON ta.Oid = p.TransferAccount
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner";
            $fromDetailTable = "acccashbankdetail d
                LEFT OUTER JOIN acccashbank p ON p.Oid = d.CashBank
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'Cash'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency bc ON bc.Oid = co.Currency
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN accaccount a ON a.Oid = d.Account
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner";
            $whereClause = "p.GCRecord IS NULL 
                AND p.Company = '{$company->Oid}' 
                AND p.Oid = '{$cashBank->Oid}'";
            $dateInitial = Carbon::parse($cashBank->Date)->format("d/M");
            //endregion

            if ($cashBank->IsTransfer) {                                               //TRANSFER
                //region TRANSFER INSERT JOURNAL CASH BANK FROM
                $arr = array_merge($arrDefault, [ 
                    "Description" => "CONCAT('{$cashBank->TypeName}: ', IFNULL(ta.Name, ''), 
                        CONCAT(' (', FORMAT(p.TransferRate,0), ' x ', FORMAT(p.TransferAmount,0), ')')
                        )", 
                    "Account" => "p.Account",           "BusinessPartner" => "NULL",
                    "Currency" => "p.Currency",         "CurrencyCashBank" => "p.TransferCurrency", 
                    "Rate" => "Rate",
                ]);
                $query = "INSERT INTO accjournal (%s, 
                    CreditAmount, CreditBase, CreditCashBank, TotalBase, 
                    DebetAmount, DebetBase, DebetCashBank)
                    SELECT %s,
                    TotalAmount, TotalBase, TransferAmount, TotalBase, 0,0,0
                    FROM {$fromParentTable} WHERE {$whereClause}";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //TRANSFER INSERT JOURNAL CASH BANK FROM
                //endregion
                
                //region TRANSFER INSERT JOURNAL CASH BANK TO
                $arr = array_merge($arrDefault, [ 
                    "Description" => "CONCAT('{$cashBank->TypeName}: ', IFNULL(a.Name, ''), 
                        CONCAT(' (', FORMAT(p.TransferRate,0), ' x ', FORMAT(p.TotalAmount,0), ')')
                        )", 
                    "Account" => "p.TransferAccount",   "BusinessPartner" => "NULL",
                    "Currency" => "p.TransferCurrency", "CurrencyCashBank" => "p.Currency", 
                    "Rate" => "p.TransferRate",
                ]);
                $query = "INSERT INTO accjournal (%s, 
                    DebetAmount, DebetBase, DebetCashBank, TotalBase, 
                    CreditAmount, CreditBase, CreditCashBank)
                    SELECT %s, TransferAmount, 
                    ROUND(TransferAmount * TransferRateBase,6), TotalAmount, 
                    ROUND(TransferAmount * TransferRateBase,6), 0,0,0
                    FROM {$fromParentTable} WHERE {$whereClause}";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //TRANSFER INSERT JOURNAL CASH BANK TO
                //endregion

            } else {                                                            //PAY/REC/INC/EXP
                //region CASHBANK BUKAN TRANSFER (Expense,Income,Payment,Receipt)
                $amountInvoiceBase = 0; $amountCashBankBase = 0;
                if ($cashBank->IsExpense) {
                    $fieldParent = "CreditAmount, CreditBase, CreditCashBank, TotalBase, DebetAmount, DebetBase, DebetCashBank ";
                    $fieldDetail = "DebetAmount, DebetBase, DebetCashBank, TotalBase, CreditAmount, CreditBase, CreditCashBank ";
                } else if ($cashBank->IsIncome) {
                    $fieldParent = "DebetAmount, DebetBase, DebetCashBank, TotalBase, CreditAmount, CreditBase, CreditCashBank ";
                    $fieldDetail = "CreditAmount, CreditBase, CreditCashBank, TotalBase, DebetAmount, DebetBase, DebetCashBank ";
                } else if ($cashBank->IsPayment) {
                    $fieldParent = "CreditAmount, CreditBase, CreditCashBank, TotalBase, DebetAmount, DebetBase, DebetCashBank ";
                    $fieldDetail = "DebetAmount, DebetBase, DebetCashBank, TotalBase, CreditAmount, CreditBase, CreditCashBank ";
                } else if ($cashBank->IsReceipt) {
                    $fieldParent = "DebetAmount, DebetBase, DebetCashBank, TotalBase, CreditAmount, CreditBase, CreditCashBank ";
                    $fieldDetail = "CreditAmount, CreditBase, CreditCashBank, TotalBase, DebetAmount, DebetBase, DebetCashBank ";
                }
                //endregion

                if ($cashBank->IsInvoice) {                                     //PAYMENT/RECEIPT
                    if (!isset($cashBank->PrepaidAccount)) {
                        //region CASHBANK (PAYMENT/RECEIPT) INSERT JOURNAL DARI INVOICE
                        $query = "SELECT d.Account as Account,
                            SUM(IFNULL(d.AmountInvoice,0)) as AmountInvoice, SUM(IFNULL(d.AmountInvoiceBase,0)) as AmountInvoiceBase,
                            SUM(IFNULL(d.AmountCashBank,0)) as AmountCashBank, SUM(IFNULL(d.AmountCashBankBase,0)) as AmountCashBankBase
                            FROM acccashbankdetail AS d
                            WHERE d.Company = '{$company->Oid}'
                            AND d.CashBank =  '{$cashBank->Oid}'
                            AND AmountInvoice != 0 AND d.TravelSalesTransactionDetail IS NULL
                            GROUP BY d.Account";
                        $result = DB::select($query); //SELECT QUERY INVOICE DG NILAI INVOICE

                        for ($i = 0; $i < count($result); $i++) {
                            $row= $result[$i];
                            $account = Account::with(["CurrencyObj"])->find($row->Account);
                            $cur = $account->CurrencyObj;
                            if ($company->Currency == $account->Currency) {
                                $amtBase = $company->CurrencyObj->round($row->AmountInvoice);
                                $amtRate = 1;
                            } else if ($company->Currency == $cashBank->Currency) {
                                $amtBase = $company->CurrencyObj->round($row->AmountCashBank);
                                $amtRate = $cashBank->Rate;
                            } else {
                                $amtBase = $cur->toBaseAmount($row->AmountInvoice, $cashBank->Rate);
                                $amtRate = $cashBank->Rate;
                            }
                            
                            if ($account->Currency != $company->Currency)
                                $strDescription = "(New: ".number_format($cashBank->Rate,0).", Old: ".number_format($cashBank->Rate,0).")";
                            else
                                $strDescription = "";
                            $arr = array_merge($arrDefault, [
                                "Description" => "CONCAT('{$cashBank->TypeName}: ', a.Name, DATE_FORMAT(p.Date, ' %d/%b '), '{$strDescription}')", 
                                "Account" => "'{$row->Account}'",           "BusinessPartner" => "p.BusinessPartner",
                                "Currency" => "'{$account->Currency}'",     "CurrencyCashBank" => "a.Currency", 
                                "Rate" => $amtRate
                            ]);
                            $query = "INSERT INTO accjournal (%s, {$fieldDetail})
                                SELECT %s,                                
                                {$row->AmountInvoice}, {$amtBase}, {$row->AmountCashBank}, {$amtBase}, 0, 0, 0
                                FROM {$fromParentTable}
                                WHERE {$whereClause}";
                            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                            DB::insert($query); //INSERT JOURNAL DARI INVOICE PAYMENT / RECEIPT
                            
                            $amountInvoiceBase += $amtBase;
                            $amountCashBankBase += $row->AmountCashBankBase;
                        }
                        //endregion
                    }
                    
                } else {                                                        //INCOME/EXPENSE
                    //region CASHBANK (INCOME/EXPENSE) INSERT JOURNAL DARI AKUN DETAIL
                    $detailAccount = "";
                    if (isset($cashBank->TravelTransaction)) {
                        if ($cashBank->IsExpense) $detailAccount = "'".$company->ExpenseInProgress."'"; else $detailAccount = "'".$company->IncomeInProgress."'";
                        $TravelTransactionOid = 'p.TravelTransaction';
                    } else {
                        $detailAccount = "d.Account";
                        $TravelTransactionOid = 'NULL';
                    }

                    $arr = array_merge($arrDefault, [
                        "Description" => "CONCAT('{$cashBank->TypeName}: ', d.Description, DATE_FORMAT(p.Date, ' %d/%b '), 
                        IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ')'))
                        )", 
                        "Account" => $detailAccount,               "BusinessPartner" => "p.BusinessPartner", 
                        "Currency" => "p.Currency",             "CurrencyCashBank" => "p.Currency",
                        "Rate" => "IFNULL(p.Rate,1)",           "TravelTransactionReport" => $TravelTransactionOid,
                    ]);
                    $query = "INSERT INTO accjournal (%s, {$fieldDetail})
                        SELECT %s,
                        SUM(IFNULL(d.AmountInvoice,0)),
                        SUM(ROUND(IFNULL(d.AmountInvoice,0) * IFNULL(p.Rate,1), IFNULL(bc.Decimal,2))),
                        SUM(IFNULL(d.AmountCashBank,0)),
                        SUM(ROUND(IFNULL(d.AmountInvoice,0) * IFNULL(p.Rate,1), IFNULL(bc.Decimal,2))), 0, 0, 0
                        FROM {$fromDetailTable}
                        WHERE {$whereClause} AND AmountInvoice != 0
                        GROUP BY jt.Oid, s.Oid, p.company, p.Oid, p.Code, p.Date, p.BusinessPartner, d.Description, d.Account, p.Currency, p.Rate, co.Currency";                        
                    $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                    DB::insert($query); //CASHBANK (INCOME/EXPENSE) INSERT JOURNAL DARI AKUN DETAIL
                    //endregion
                }

                //region CASHBANK (Inc,Exp,Pay,Rec) INSERT JOURNAL PARENT (LAWAN)
                $arr = array_merge($arrDefault, [
                    "Description" => "CONCAT('{$cashBank->TypeName}: ', IFNULL(bp.Name, ''), DATE_FORMAT(p.Date, ' %d/%b '), 
                        IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ' x ', FORMAT(p.TotalAmount,2), ')'))
                        )", 
                    // Payment: A ONE HOTEL 10/Aug (15,000 x 1,150.00)
                    "Account" => "p.Account",           "BusinessPartner" => "NULL", 
                    "Currency" => "p.Currency",         "CurrencyCashBank" => "p.Currency", 
                    "Rate" => "IFNULL(p.Rate,1)",
                ]);
                $query = "INSERT INTO accjournal (%s, {$fieldParent})
                    SELECT %s, p.TotalAmount, p.TotalBase, p.TotalAmount, p.TotalBase, 0, 0, 0
                    FROM {$fromParentTable}
                    WHERE {$whereClause} AND TotalBase != 0";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //CASHBANK (Inc,Exp,Pay,Rec) INSERT PARENT (LAWAN)
                //endregion
                
                //region CASHBANK (Inc,Exp,Pay,Rec) INSERT JOURNAL PREPAID
                $prepaidDiffAmount = 0;
                if (isset($cashBank->PrepaidAccount)) {
                    
                    $fromDetailTable2 = "
                        LEFT OUTER JOIN accaccount acp ON acp.Oid = p.PrepaidAccount";
                    $fromDetailTable2 = $fromParentTable.$fromDetailTable2;
                
                    $arr = array_merge($arrDefault, [
                        "Description" => "CONCAT('{$cashBank->TypeName}: ', IFNULL(bp.Name, ''), ' - ', a.Name, DATE_FORMAT(p.Date, ' %d/%b '), 
                            IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.PrepaidRate,0), ')'))
                            )", 
                        "Account" => "p.PrepaidAccount",     "BusinessPartner" => "p.BusinessPartner", 
                        "Currency" => "acp.Currency",             "CurrencyCashBank" => "p.Currency",
                        "Rate" => "IFNULL(p.PrepaidRate,1)",
                    ]);
                    if ($cashBank->PrepaidAccountObj->Currency == $cashBank->AccountObj->Currency) {
                        $prepaidAmount = $cashBank->PrepaidAmount;
                    } else {
                        if ($cashBank->PrepaidAccountObj->CurrencyObj->Code == 'IDR')
                        $prepaidAmount = $cashBank->PrepaidAmount * $cashBank->PrepaidRate; //TODO: CURRATE
                        else
                        $prepaidAmount = $cashBank->PrepaidAmount / $cashBank->PrepaidRate; //TODO: CURRATE
                    }                        
                    $prepaidBase = $cashBank->PrepaidAmount * $cashBank->Rate; //TODO: CURRATE
                    // ROUND(p.PrepaidAmount / IFNULL(p.PrepaidRate,1),c.Decimal)
                    $query = "INSERT INTO accjournal (%s, {$fieldDetail})
                        SELECT %s,
                        ROUND({$prepaidAmount},c.Decimal), {$prepaidBase},
                        $cashBank->PrepaidAmount, {$prepaidBase},
                        0, 0, 0
                        FROM {$fromDetailTable2}
                        WHERE {$whereClause} AND PrepaidAmount != 0";
                    $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                    DB::insert($query); //INSERT DARI NILAI PREPAID
                }
                //endregion

                //region CASHBANK (Inc,Exp,Pay,Rec) INSERT JOURNAL ADDITIONAL
                $arr = array_merge($arrDefault, [
                    "Description" => "CONCAT('{$cashBank->TypeName}: ', IFNULL(bp.Name, ''), ' - ', a.Name, DATE_FORMAT(p.Date, ' %d/%b '), 
                        IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ')'))
                        )", 
                    "Account" => "p.AdditionalAccount",     "BusinessPartner" => "p.BusinessPartner", 
                    "Currency" => "p.Currency",             "CurrencyCashBank" => "p.Currency",
                    "Rate" => "IFNULL(p.Rate,1)",
                ]);
                $query = "INSERT INTO accjournal (%s, {$fieldDetail})
                    SELECT %s,
                    p.AdditionalAmount,
                    ROUND(p.AdditionalAmount * IFNULL(p.Rate,1),c.Decimal), p.AdditionalAmount,
                    ROUND(p.AdditionalAmount * IFNULL(p.Rate,1),c.Decimal), 0, 0, 0
                    FROM {$fromParentTable}
                    WHERE {$whereClause} AND AdditionalAmount != 0";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //INSERT DARI NILAI ADDITIONAL
                //endregion
        
                //region CASHBANK (Inc,Exp,Pay,Rec) INSERT JOURNAL DISKON
                $arr = array_merge($arrDefault, [
                    "Description" => "CONCAT('{$cashBank->TypeName}: ', IFNULL(bp.Name, ''), ' - ', a.Name, DATE_FORMAT(p.Date, ' %d/%b '), 
                        IF(p.Currency = co.Currency, '', CONCAT(' (', FORMAT(p.Rate,0), ')'))
                        )", 
                    "Account" => "p.DiscountAccount",           "BusinessPartner" => "NULL", 
                    "Currency" => "p.Currency",                 "CurrencyCashBank" => "p.Currency",
                    "Rate" => "IFNULL(p.Rate,1)",
                ]);
                $query = "INSERT INTO accjournal (%s, {$fieldParent})
                    SELECT %s, 
                    p.DiscountAmount,
                    ROUND(p.DiscountAmount * IFNULL(p.Rate,1),c.Decimal), p.DiscountAmount,
                    ROUND(p.DiscountAmount * IFNULL(p.Rate,1),c.Decimal), 0, 0, 0
                    FROM {$fromParentTable}
                    WHERE {$whereClause} AND DiscountAmount != 0";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query); //INSERT DARI NILAI DISKON
                //endregion
            }
            DB::update("Update acccashbank SET Status = '{$status->Oid}' WHERE Oid = '{$cashBank->Oid}' OR Parent = '{$cashBank->Oid}';");
        });
    }

    public function unpost($id)
    {
        DB::transaction(function() use ($id) {
            $cashBank = CashBank::findOrFail($id);
            if ($this->isPeriodClosed($cashBank->Date)) {
                $this->throwPeriodIsClosedError($cashBank->Date);
            }
            $cashBank->Journals()->delete();
            CashBank::where('Oid', $id)
            ->orWhere('Oid', $id)
            ->update([
                'Status' => Status::entry()->value('Oid'),
            ]);
        });
    }

    public function cancelled($id)
    {
        DB::transaction(function() use ($id) {
            $cashBank = CashBank::findOrFail($id);
            if ($this->isPeriodClosed($cashBank->Date)) {
                $this->throwPeriodIsClosedError($cashBank->Date);
            }
            $cashBank->Journals()->delete();
            CashBank::where('Oid', $id)
            ->orWhere('Oid', $id)
            ->update([
                'Status' => Status::cancelled()->value('Oid'),
            ]);
        });
    }

}
