<?php

namespace App\Core\Master\Traits;

use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\Currency;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\Status;

trait ItemPurchaseAmountByAge
{
    /**
    * Get the display Purchase amount
    * 
    * @param Currency $currency
    * @param User $user
    * @return float
    */
   public function getPurchaseAmountByAge($age = 13, $user = null)
   {
        if (!isset($user)) $user = \Illuminate\Support\Facades\Auth::user();

        if ($age > 1000) $age = now()->year - $age;
        if ($age == null) 
            $age = "adult";
        else if ($age > 12)
            $age = "adult";
        else if ($age > 2)
            $age = "child";
        else
           $age = "infant";
        
       $field = "Purchase".$age;
       if ($this->IsParent == 0) {
            $query = "SELECT tp.PurchaseCurrency, {$field} AS PurchaseAmount
                FROM mstitem d 
                LEFT OUTER JOIN pospriceage tp ON tp.Item = d.Oid
                WHERE d.Oid = '{$this->Oid}'
                AND tp.DateFrom <= NOW()
                AND tp.DateUntil >= NOW()
                ORDER BY {$field}
                LIMIT 1";
       } else {
            $query = "SELECT tp.PurchaseCurrency, {$field} AS PurchaseAmount
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
       return $data[0]->{'PurchaseAmount'};
   }

   /**
    * Get the display Purchase amount
    * 
    * @param Currency $currency
    * @param User $user
    * @return float
    */
   public function getPurchaseAmountDisplayByAge($age = 13, $currency = null, $user = null)
   {
        $currency = $this->getCurrency($currency);
        $amount = $this->getPurchaseAmountByAge($age, $user);
        return $this->PurchaseCurrencyObj->convertRate($currency, $amount);
   }

    /**
     * Get the display Purchase amount
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountDisplayByAgeForParent($currency = null, $user = null)
    {
        return $this->getPurchaseAmountDisplayByAge(30, $currency, $user);
    }
    
    /**
     * Get the base Purchase amount
     * 
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountBaseByAge($age, $user = null)
    {
        $amount = $this->getPurchaseAmountByAge($age, $user);
        if (is_null($this->PurchaseCurrency)) return $amount;
        $currency = $this->PurchaseCurrencyObj;
        return $currency->round($currency->getRate()->convertAmount($currency->Method, $amount));
    }

    /**
     * Get adult Purchase amount
     * 
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountAdult($user = null)
    {
        return $this->getPurchaseAmountByAge(13, $user);
    }

     /**
     * Get adult Purchase amount display
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountDisplayAdult($currency = null, $user = null)
    {
        return $this->getPurchaseAmountDisplayByAge(13, $currency, $user);
    }

    /**
     * Get adult Purchase amount base
     * 
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountBaseAdult($user = null)
    {
        return $this->getPurchaseAmountBaseByAge(13, $user);
    }

    /**
     * Get adult Purchase amount
     * 
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountChild($user = null)
    {
        return $this->getPurchaseAmountByAge(11, $user);
    }

     /**
     * Get child Purchase amount base
     * 
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountBaseChild($user = null)
    {
        return $this->getPurchaseAmountBaseByAge(11, $user);
    }

    /**
     * Get child Purchase amount display
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountDisplayChild($currency = null, $user = null)
    {
        return $this->getPurchaseAmountDisplayByAge(11, $currency, $user);
    }


    /**
     * Get adult Purchase amount
     * 
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountInfant($user = null)
    {
        return $this->getPurchaseAmountByAge(1, $user);
    }

     /**
     * Get infant Purchase amount display
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountDisplayInfant($currency = null, $user = null)
    {
        return $this->getPurchaseAmountDisplayByAge(1, $currency, $user);
    }

     /**
     * Get infant Purchase amount base
     * 
     * @param User $user
     * @return float
     */
    public function getPurchaseAmountBaseInfant($user = null)
    {
        return $this->getPurchaseAmountBaseByAge(1, $user);
    }
}
