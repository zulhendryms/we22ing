<?php

namespace App\Core\Master\Traits;

use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\Currency;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\Status;

trait ItemSalesAmount
{
    /**
     * Get currency from argument/session/company
     * @return Currency
     */
    protected function getCurrency($currency = null)
    {
        if (is_null($currency) && session()->isStarted()) {
            $currency = session(config('constants.currency'));
        }
        if (is_null($currency)) $currency = company()->Currency;
        if (is_string($currency)) return Currency::findOrFail($currency);
        return $currency;
    }

     /**
     * Get the purchase amount
     * @param User $user
     * @return float
     */
    public function getPurchaseAmount($user = null)
    {
        if (!isset($user)) $user = \Illuminate\Support\Facades\Auth::user();
        if (!isset($user)) return $this->PurchaseAmount;
        // if (!isset($user->BusinessPartner)) return $this->PurchaseAmount;
        // $businessPartner = $user->BusinessPartnerObj;
        // $level = '';
        // if (isset($businessPartner)) {
        //     if (isset($businessPartner->PurchasePriceLevel)) {
        //         $level = $businessPartner->PurchasePriceLevel;
        //     }
        // }
        // return $this->{'PurchaseAmount'.$level};
        return $this->PurchaseAmount;
    }

    /**
     * Get the sales amount
     * @param User $user
     * @return float
     */
    public function getSalesAmount($user = null)
    {
        // if (!isset($user)) $user = \Illuminate\Support\Facades\Auth::user();
        // if (!isset($user)) return $this->SalesAmount;
        // $level = $user->getSalesPriceLevel();
        if (isset($userOid)) $user = User::findOrFail($user);
        else $user = \Illuminate\Support\Facades\Auth::user();

        if (isset($user)) $level = $user->getSalesPriceLevel();
        else $level = "";
        return $this->{'SalesAmount'.$level};
    }

    /**
     * Get the display sales amount
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getSalesAmountParent($user = null)
    {
        if (!isset($user)) $user = \Illuminate\Support\Facades\Auth::user();
        if (!isset($user)) 
            $level = ''; 
        else 
            $level = $user->getSalesPriceLevel();
        if (count($this->Details) < 1) return 0;
        $data = $this->Details()->where('IsActive', 1)->orderBy('SalesAmount'.$level)->first();        
        return $data->{'SalesAmount'.$level};
    }

    /**
     * Get the display sales amount
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getUsualAmountParent($user = null)
    {
        if (!isset($user)) $user = \Illuminate\Support\Facades\Auth::user();
        if (!isset($user)) 
            $level = ''; 
        else 
            $level = $user->getSalesPriceLevel();
        if (count($this->Details) < 1) return 0;
        $data = $this->Details()->where('IsActive', 1)->orderBy('SalesAmount'.$level)->first();        
        return $data->UsualAmount;
    }


    /**
     * Get the display sales amount
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getSalesAmountDisplay($currency = null, $user = null)
    {
        $currency = $this->getCurrency($currency);
        $amount = $this->getSalesAmount($user);
        return $this->SalesCurrencyObj->convertRate($currency, $amount);
    }

    /**
     * Get the display sales amount
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getSalesAmountDisplayParent($currency = null, $user = null)
    {
        $currency = $this->getCurrency($currency);
        $amount = $this->getSalesAmountParent($user);
        return $this->SalesCurrencyObj->convertRate($currency, $amount);
    }
    /**
     * Get the display sales amount
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getUsualAmountDisplayParent($currency = null, $user = null)
    {
        $currency = $this->getCurrency($currency);
        $amount = $this->getUsualAmountParent($user);
        return $this->SalesCurrencyObj->convertRate($currency, $amount);
    }

    /**
     * Get the base sales amount
     * 
     * @param User $user
     * @return float
     */
    public function getSalesAmountBase($user = null)
    {
        $amount = $this->getSalesAmount($user);
        if (is_null($this->SalesCurrency)) return $amount;
        $currency = $this->SalesCurrencyObj;
        return $currency->round($currency->getRate()->convertAmount($currency->Method, $amount));
    }

    /**
     * Get total quantity sales
     * 
     * @return float
     */
    public function getTotalQuantitySales()
    {
        return $this->PointOfSales()
        ->where('Status', '<>', Status::cancelled()->value('Oid'))
        ->sum('pospointofsale.Quantity');
    }

    /**
     * Get total quantity sold
     * 
     * @return float
     */
    public function getTotalQuantitySold() {
        return ($this->QuantitySold ? $this->QuantitySold : 0) + ($this->InternalSold ? $this->InternalSold : 0);
    }

    /**
     * Get saving percentage
     * 
     * @param User $user
     * @return float
     */
    public function getSavingPercentage($user = null) {
        $amount = $this->getSalesAmount($user);
        return number_format(100 - ((($amount ? $amount : 0) / ($this->UsualAmount ? $this->UsualAmount : 1))* 100), 0).'% OFF';
    }

    /**
     * Get saving percentage
     * 
     * @param User $user
     * @return float
     */
    public function getSavingPercentageParent($user = null) {
        $salesAmount = $this->getSalesAmountParent($user);
        $usualAmount = $this->getUsualAmountParent($user);
        return number_format(100 - ((($salesAmount ? $salesAmount : 0) / ($usualAmount ? $usualAmount : 1))* 100), 0).'% OFF';
    }

    /**
     * Get saving amount
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getSavingAmount($currency = null, $user = null) {
        $currency = $this->getCurrency($currency);
        return $this->SalesCurrencyObj->convertRate($currency, $UsualAmount) - $this->getSalesAmountDisplay($currency, $user);
    }
    
     /**
     * Get sales amount display text
     * 
     * @param Currency $currency
     * @param User $user
     * @return string
     */
    public function getSalesAmountDisplayText($currency = null, $user = null)
    {
        $currency = $this->getCurrency($currency);
        $amount = $this->getSalesAmountDisplay($currency, $user);
        return $currency->Symbol.' '.number_format($amount, $currency->Decimal);
    }

    /**
     * Get sales amount display text
     * 
     * @param Currency $currency
     * @param User $user
     * @return string
     */
    public function getSalesAmountDisplayTextParent($currency = null, $user = null)
    {
        $currency = $this->getCurrency($currency);
        $amount = $this->getSalesAmountDisplayParent($currency, $user);
        return $currency->Symbol.' '.number_format($amount, $currency->Decimal);
    }

    /**
     * Get sales amount display text
     * 
     * @param Currency $currency
     * @param User $user
     * @return string
     */
    public function getUsualAmountDisplayTextParent($currency = null, $user = null)
    {
        $currency = $this->getCurrency($currency);
        $amount = $this->getUsualAmountDisplayParent($currency, $user);
        return $currency->Symbol.' '.number_format($amount, $currency->Decimal);
    }

    /**
     * Get the display sales amount
     * 
     * @param Currency $currency
     * @param User $user
     * @return float
     */
    public function getUsualAmountDisplay($currency = null, $user = null)
    {
        $currency = $this->getCurrency($currency);
        return $this->SalesCurrencyObj->convertRate($currency, $this->UsualAmount);
    }
    
     /**
     * Get usual amount display text
     * 
     * @param Currency $currency
     * @param User $user
     * @return string
     */
    public function getUsualAmountDisplayText($currency = null, $user = null)
    {
        $currency = $this->getCurrency($currency);
        $amount =  $this->getUsualAmountDisplay($currency, $user);
        return $currency->Symbol.' '.number_format($amount, $currency->Decimal);
    }

}