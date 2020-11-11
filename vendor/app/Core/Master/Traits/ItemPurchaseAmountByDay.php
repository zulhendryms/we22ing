<?php

namespace App\Core\Master\Traits;

use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\Currency;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\Status;
use Carbon\Carbon;

trait ItemPurchaseAmountByDay
{
    /**
    * Get the display Purchase amount
    * 
    * @param Currency $currency
    * @param User $user
    * @return float
    */
   public function getPurchaseAmountByDay($date = null, $user = null)
   {
        if (!isset($user)) $user = \Illuminate\Support\Facades\Auth::user();
        
        if ($date == null) $date = now();
        if (Carbon::parse($date)->isWeekend()) {
            $field = "Weekend";
        } else {
            $field = "Weekday";
        }
        
       $field = "Purchase".$field;
       if ($this->IsParent == 0) {
            $query = "SELECT tp.PurchaseCurrency, {$field} AS PurchaseAmount
                FROM mstitem d 
                LEFT OUTER JOIN pospriceday tp ON tp.Item = d.Oid
                WHERE d.Oid = '{$this->Oid}'
                AND tp.DateFrom <= NOW()
                AND tp.DateUntil >= NOW()
                ORDER BY {$field}
                LIMIT 1";
       } else {
            $query = "SELECT tp.PurchaseCurrency, {$field} AS PurchaseAmount
                FROM mstitem p 
                LEFT OUTER JOIN mstitem d ON p.Oid = d.ParentOid
                LEFT OUTER JOIN pospriceday tp ON tp.Item = d.Oid
                WHERE p.Oid = '{$this->Oid}'
                AND tp.DateFrom <= NOW()
                AND tp.DateUntil >= NOW()
                ORDER BY {$field}
                LIMIT 1";
       }       
       $data = DB::select($query);
       if (empty($data)) return 0;
       return $data[0]->{'PurchaseAmount'};
   }

   /**
    * Get the display Purchase amount
    * 
    * @param Currency $currency
    * @param User $user
    * @return float
    */
   public function getPurchaseAmountDisplayByDay($date = null, $currency = null, $user = null)
   {
        $currency = $this->getCurrency($currency);
        $amount = $this->getPurchaseAmountByDay($date, $user);
        return $this->PurchaseCurrencyObj->convertRate($currency, $amount);
   }

    /**
     * Get the display Purchase amount
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountDisplayByDayForParent($currency = null, $user = null)
    {
        return $this->getPurchaseAmountDisplayByDay(30, $currency, $user);
    }
    
    /**
     * Get the base Purchase amount
     * 
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountBaseByDay($date, $user = null)
    {
        $amount = $this->getPurchaseAmountByDay($date, $user);
        if (is_null($this->PurchaseCurrency)) return $amount;
        $currency = $this->PurchaseCurrencyObj;
        return $currency->round($currency->getRate()->convertAmount($currency->Method, $amount));
    }

    /**
     * Get the Purchase amount weekend
     * 
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountWeekend($user = null)
    {
        return $this->getPurchaseAmountByDay(Carbon::parse('this saturday'), $user);
    }

    /**
     * Get the Purchase amount weekend display
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountDisplayWeekend($currency = null, $user = null)
    {
        return $this->getPurchaseAmountDisplayByDay(Carbon::parse('this saturday'), $currency, $user);
    }

    /**
     * Get the Purchase amount weekday
     * 
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountWeekday($user = null)
    {
        return $this->getPurchaseAmountDisplayByDay(Carbon::parse('this monday'), $user);
    }

    /**
     * Get the Purchase amount weekday display
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountDisplayWeekday($currency = null, $user = null)
    {
        return $this->getPurchaseAmountDisplayByDay(Carbon::parse('this monday'), $currency, $user);
    }
}
