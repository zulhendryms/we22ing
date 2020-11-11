<?php

namespace App\Core\Master\Traits;

use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\Currency;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\Status;
use Carbon\Carbon;

trait ItemSalesAmountByDay
{
    /**
    * Get the display sales amount
    * 
    * @param Currency $currency
    * @param User $user
    * @return float
    */
   public function getSalesAmountByDay($date = null, $userOid = null, $type = 'FIT')
   {
        if (isset($userOid)) $user = User::findOrFail($userOid);
        else $user = \Illuminate\Support\Facades\Auth::user();

        if (isset($user)) $level = $user->getSalesPriceLevel();
        else $level = "";

        if ($date == null) $date = Carbon::now();
        if (Carbon::parse($date)->isWeekend()) {
            $field = "Weekend";
        } else {
            $field = "Weekday";
        }
        
       $field = "Sell".$type.$field.$level;
       if ($this->IsParent == 0) {
            $query = "SELECT tp.SalesCurrency, {$field} AS SalesAmount
                FROM mstitem d 
                LEFT OUTER JOIN pospriceday tp ON tp.Item = d.Oid
                WHERE d.Oid = '{$this->Oid}'
                AND tp.DateFrom <= NOW()
                AND tp.DateUntil >= NOW()
                ORDER BY {$field}
                LIMIT 1";
       } else {
            $query = "SELECT tp.SalesCurrency, {$field} AS SalesAmount
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
       return $data[0]->{'SalesAmount'};
   }

   /**
    * Get the display sales amount
    * 
    * @param Currency $currency
    * @param User $user
    * @return float
    */
   public function getSalesAmountDisplayByDay($date = null, $currency = null, $user = null, $type = 'FIT')
   {
        $currency = $this->getCurrency($currency);
        $amount = $this->getSalesAmountByDay($date, $user, $type);
        return $this->SalesCurrencyObj->convertRate($currency, $amount);
   }

    /**
     * Get the display sales amount
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getSalesAmountDisplayByDayForParent($date = null, $currency = null, $user = null, $type = 'FIT')
    {
        if ($date == null) $date = Carbon::now();
        return $this->getSalesAmountDisplayByDay($date, $currency, $user, $type);
    }
    
    /**
     * Get the base sales amount
     * 
     * @param User $user
     * @return float
     */
    public function getSalesAmountBaseByDay($date, $user = null, $type = 'FIT')
    {
        $amount = $this->getSalesAmountByDay($date, $user, $type);
        if (is_null($this->SalesCurrency)) return $amount;
        $currency = $this->SalesCurrencyObj;
        return $currency->round($currency->getRate()->convertAmount($currency->Method, $amount));
    }

    /**
     * Get the sales amount weekend
     * 
     * @param User $user
     * @return float
     */
    public function getSalesAmountWeekend($user = null, $type = 'FIT')
    {
        return $this->getSalesAmountByDay(Carbon::parse('this saturday'), $user, $type);
    }

    /**
     * Get the sales amount weekend display
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getSalesAmountDisplayWeekend($currency = null, $user = null, $type = 'FIT')
    {
        return $this->getSalesAmountDisplayByDay(Carbon::parse('this saturday'), $currency, $user, $type);
    }

    /**
     * Get the sales amount weekday
     * 
     * @param User $user
     * @return float
     */
    public function getSalesAmountWeekday($user = null, $type = 'FIT')
    {
        return $this->getSalesAmountDisplayByDay(Carbon::parse('this monday'), $user, $type);
    }

    /**
     * Get the sales amount weekday display
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getSalesAmountDisplayWeekday($currency = null, $user = null, $type = 'FIT')
    {
        return $this->getSalesAmountDisplayByDay(Carbon::parse('this monday'), $currency, $user, $type);
    }
}
