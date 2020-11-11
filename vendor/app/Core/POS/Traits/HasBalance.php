<?php

namespace App\Core\POS\Traits;

use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\Currency;


trait HasBalance
{

    /**
     * Get the balances for user
     */
    public function WalletBalances()
    {
        return $this->hasMany("App\Core\POS\Entities\WalletBalance", $this->getBalanceLocalKey(), "Oid");
    }

    /**
     * Get current user balance amount
     * @param Currency|string|null $currency
     * @return float User's current balance
     */
    public function getBalance($currency = null)
    {
        $key = $this->getBalanceLocalKey();
        if (is_null($currency)) $currency = $this->Currency ?? $this->CompanyObj->Currency;

        $query = "SELECT SUM(IFNULL(DebetAmount, 0) - IFNULL(CreditAmount, 0)) as Value 
            FROM poswalletbalance WHERE {$key} = '{$this->Oid}' 
            AND Status = (SELECT Oid FROM sysstatus WHERE Code = 'posted')
            AND Currency = %s;";
        if ($currency instanceof Currency) { // The parameter is currency instance
            $query = sprintf($query, "'{$currency->Oid}'");
        }else {
            if (strlen($currency) < 36) { // The parameter is currency code
                // Find by code
                $query = sprintf($query, "(SELECT Oid FROM mstcurrency WHERE Code = '{$currency}')");
            } else {
                $query = sprintf($query, "'{$currency}'");
            }
        }
        $result = DB::select($query);
        return floatval($result[0]->Value) ?? 0;
    }

    public function getBalanceLocalKey()
    {
        return $this->balanceLocalKey ?? (new \ReflectionClass($this))->getShortName();
    }
}