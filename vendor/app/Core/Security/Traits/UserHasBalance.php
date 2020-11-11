<?php
namespace App\Core\Security\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Trait for User and WalletBalance association
 */
trait UserHasBalance 
{
    /**
     * Get the balances for user
     */
    public function WalletBalancesETH()
    {
        return $this->hasMany("App\Core\POS\Entities\WalletBalance", "Oid", "User");
    }

    /**
     * Get current user balance amount
     * @param string|null $currencyId CurrencyId / code
     * @return float User's current balance
     */
    public function getBalanceETH($currencyId = null)
    {        
        if (is_null($currencyId)) $currencyId = $this->Currency ?? $this->CompanyObj->Currency;
        // $query = "SELECT SUM(IFNULL(DebetAmount, 0) - IFNULL(CreditAmount, 0)) as Value 
        //     FROM poswalletbalance WHERE User = '{$this->Oid}' 
        //     AND Status = (SELECT Oid FROM sysstatus WHERE Code = 'posted')
        //     AND GCRecord IS NULL
        //     AND Currency = %s;";
        $query = "SELECT 
            ((IFNULL(AmtPosted,0)-IFNULL(AmtPendingMinus,0)) * IFNULL(e.WithdrawPercentage,0) /100) + IFNULL(AmtWithdraw,0) AmtAvailable
            FROM mstcurrency c
            LEFT OUTER JOIN ethcurrency e ON c.Oid = e.Oid
            LEFT OUTER JOIN (
                SELECT wb.Currency, SUM(IFNULL(wb.DebetAmount,0) - IFNULL(wb.CreditAmount,0)) AS AmtPosted
                FROM poswalletbalance wb LEFT OUTER JOIN sysstatus s ON wb.Status = s.Oid
                WHERE wb.Type != 'Withdraw' AND s.Code = 'Posted' AND wb.GCRecord IS NULL AND wb.User = '{$this->Oid}'
                GROUP BY wb.Currency) tbpost ON tbpost.Currency = c.Oid
            LEFT OUTER JOIN (
                SELECT wb.Currency, SUM(IFNULL(wb.DebetAmount,0) - IFNULL(wb.CreditAmount,0)) AS AmtWithdraw
                FROM poswalletbalance wb LEFT OUTER JOIN sysstatus s ON wb.Status = s.Oid
                WHERE wb.Type = 'Withdraw' AND s.Code != 'Cancel' AND wb.GCRecord IS NULL AND wb.User = '{$this->Oid}' 
                GROUP BY wb.Currency) tbwith ON tbwith.Currency = c.Oid
            LEFT OUTER JOIN (
                SELECT wb.Currency, SUM(IFNULL(wb.DebetAmount,0) + IFNULL(wb.CreditAmount,0)) AS AmtPending
                FROM poswalletbalance wb LEFT OUTER JOIN sysstatus s ON wb.Status = s.Oid
                WHERE s.Code != 'Posted' AND s.Code != 'Cancel' AND wb.GCRecord IS NULL AND wb.User = '{$this->Oid}' 
                GROUP BY wb.Currency) tbpend ON tbpend.Currency = c.Oid
            LEFT OUTER JOIN (
                SELECT wb.Currency, SUM(IFNULL(wb.CreditAmount,0)) AS AmtPendingMinus
                FROM poswalletbalance wb LEFT OUTER JOIN sysstatus s ON wb.Status = s.Oid
                WHERE s.Code != 'Posted' AND wb.Type != 'Withdraw' AND s.Code != 'Cancel' AND wb.GCRecord IS NULL AND wb.User = '{$this->Oid}' 
                GROUP BY wb.Currency) tbminus ON tbminus.Currency = c.Oid
            WHERE c.GCRecord IS NULL 
            AND IFNULL(tbpend.AmtPending,0) + IFNULL(tbpost.AmtPosted,0) + IFNULL(tbwith.AmtWithdraw,0) != 0 AND c.Oid = %s;";
        if (strlen($currencyId) < 36) { // The parameter is currency code
            // Find by code
            $query = sprintf($query, "(SELECT Oid FROM mstcurrency WHERE Code = '{$currencyId}')");
        } else {
            // Find by id
            $query = sprintf($query, "'{$currencyId}'");
        }
        $result = DB::select($query);
        if (isset($result[0]))             
            return $result[0]->AmtAvailable ?? 0; // return floatval($result[0]->AmtAvailable) ?? 0;            
        else 
            return 0;
    }
}
