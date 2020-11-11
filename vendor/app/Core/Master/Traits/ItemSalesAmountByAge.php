<?php

namespace App\Core\Master\Traits;

use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\Currency;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\Status;

trait ItemSalesAmountByAge
{
    /**
    * Get the display sales amount
    * 
    * @param Currency $currency
    * @param User $user
    * @return float
    */
   public function getSalesAmountByAge($age = 13, $user = null, $type = 'FIT')
   {
        if (isset($userOid)) $user = User::findOrFail($user);
        else $user = \Illuminate\Support\Facades\Auth::user();

        if (isset($user)) $level = $user->getSalesPriceLevel();
        else $level = "";

        if ($age > 1000) $age = now()->year - $age;
        if ($age == null) 
            $age = "adult";
        else if ($age > 12)
            $age = "adult";
        else if ($age > 2)
            $age = "child";
        else
           $age = "infant";
        
       $field = "Sell".$type.$age.$level;
       if ($this->IsParent == 0) {
            $query = "SELECT tp.SalesCurrency, {$field} AS SalesAmount
                FROM mstitem d 
                LEFT OUTER JOIN pospriceage tp ON tp.Item = d.Oid
                WHERE d.Oid = '{$this->Oid}'
                AND tp.DateFrom <= NOW()
                AND tp.DateUntil >= NOW()
                ORDER BY {$field}
                LIMIT 1";
       } else {
            $query = "SELECT tp.SalesCurrency, {$field} AS SalesAmount
                FROM mstitem p 
                LEFT OUTER JOIN mstitem d ON p.Oid = d.ParentOid
                LEFT OUTER JOIN pospriceage tp ON tp.Item = d.Oid
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
   public function getSalesAmountDisplayByAge($age = 13, $currency = null, $user = null, $type = 'FIT')
   {
        $currency = $this->getCurrency($currency);
        $amount = $this->getSalesAmountByAge($age, $user, $type);
        return $this->SalesCurrencyObj->convertRate($currency, $amount);
   }

    /**
     * Get the display sales amount
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getSalesAmountDisplayByAgeForParent($currency = null, $user = null, $type = 'FIT')
    {
        return $this->getSalesAmountDisplayByAge(30, $currency, $user, $type);
    }
    
    /**
     * Get the base sales amount
     * 
     * @param User $user
     * @return float
     */
    public function getSalesAmountBaseByAge($age, $user = null, $type = 'FIT')
    {
        $amount = $this->getSalesAmountByAge($age, $user, $type);
        if (is_null($this->SalesCurrency)) return $amount;
        $currency = $this->SalesCurrencyObj;
        return $currency->round($currency->getRate()->convertAmount($currency->Method, $amount));
    }

    /**
     * Get adult sales amount
     * 
     * @param User $user
     * @return float
     */
    public function getSalesAmountAdult($user = null, $type = 'FIT')
    {
        return $this->getSalesAmountByAge(13, $user, $type);
    }

     /**
     * Get adult sales amount display
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getSalesAmountDisplayAdult($currency = null, $user = null, $type = 'FIT')
    {
        return $this->getSalesAmountDisplayByAge(13, $currency, $user, $type);
    }

    /**
     * Get adult sales amount base
     * 
     * @param User $user
     * @return float
     */
    public function getSalesAmountBaseAdult($user = null, $type = 'FIT')
    {
        return $this->getSalesAmountBaseByAge(13, $user, $type);
    }

    /**
     * Get adult sales amount
     * 
     * @param User $user
     * @return float
     */
    public function getSalesAmountChild($user = null, $type = 'FIT')
    {
        return $this->getSalesAmountByAge(11, $user, $type);
    }

     /**
     * Get child sales amount base
     * 
     * @param User $user
     * @return float
     */
    public function getSalesAmountBaseChild($user = null, $type = 'FIT')
    {
        return $this->getSalesAmountBaseByAge(11, $user, $type);
    }

    /**
     * Get child sales amount display
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getSalesAmountDisplayChild($currency = null, $user = null, $type = 'FIT')
    {
        return $this->getSalesAmountDisplayByAge(11, $currency, $user, $type);
    }


    /**
     * Get adult sales amount
     * 
     * @param User $user
     * @return float
     */
    public function getSalesAmountInfant($user = null, $type = 'FIT')
    {
        return $this->getSalesAmountByAge(1, $user, $type);
    }

     /**
     * Get infant sales amount display
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getSalesAmountDisplayInfant($currency = null, $user = null, $type = 'FIT')
    {
        return $this->getSalesAmountDisplayByAge(1, $currency, $user, $type);
    }

     /**
     * Get infant sales amount base
     * 
     * @param User $user
     * @return float
     */
    public function getSalesAmountBaseInfant($user = null, $type = 'FIT')
    {
        return $this->getSalesAmountBaseByAge(1, $user, $type);
    }
}
