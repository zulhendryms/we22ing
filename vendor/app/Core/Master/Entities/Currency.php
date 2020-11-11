<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use Carbon\Carbon;
use App\Core\Base\Traits\BelongsToCompany;

class Currency extends BaseModel {
    use Activable, BelongsToCompany;
    // use Activable;
    protected $table = 'mstcurrency';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Code;
        }
        return parent::__get($key);
    }
    
    public function CompanyObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "Company", "Oid"); }
    public function CurrencyRates() { return $this->hasMany('App\Core\Master\Entities\CurrencyRate', 'Currency', 'Oid'); }

    public function scopeIDR() { return $this->where('Code', 'IDR')->where('Company', config('app.company_id')); }

    public function round($number) {
        return round($number, $this->Decimal);
    }    
    
    public function getRate($date = null) {
        if (!$date) $date = now();
        // $strDate = Carbon::parse($date)->addDay()->format('Ym'); //20180923 tdk tau kenapa diformat YYYMM, addDay juga tdk tau benar tdk
        $strDate = Carbon::parse($date)->addDay()->toDateString();
        return $this->CurrencyRates()->where('Date', '<', $strDate)->orderBy('Date', 'DESC')->first();
    }

    public function convertRate($currency, $amount, $date = null) {        
        if ($amount == 0) return $amount;
        if ($currency instanceof Currency) {
            if ($currency->Oid == $this->Oid) return $amount; 
        } else {
            if ($currency == $this->Oid) return $amount;
            $currency = Currency::find($currency);
        }
        if (!$date) $date = now();
        $fromCurrencyToBase = $this->calcFromCurToBase($this, $amount, $this->getRate($date) ? $this->getRate($date)->MidRate : 1);
        $fromBaseToCurrency = $this->calcFromBaseToCur($currency, $fromCurrencyToBase, $currency->getRate($date) ? $currency->getRate($date)->MidRate : 1);
        return $fromBaseToCurrency;
    }

    public function convertRateObject($currency, $amounts) {        
        if ($currency instanceof Currency) {
            if ($currency->Oid == $this->Oid) return $amounts; 
        } else {
            if ($currency == $this->Oid) return $amounts;
            $currency = Currency::find($currency);
        }
        $date = now();
        $fromRate = $this->getRate($date)->MidRate;
        $toRate = $currency->getRate($date)->MidRate;
        foreach ($amounts as $field => $value) {
            $fromCurrencyToBase = $this->calcFromCurToBase($this, $value, $fromRate);
            $fromBaseToCurrency = $this->calcFromBaseToCur($currency, $fromCurrencyToBase, $toRate);
            $amounts->{$field} = $fromBaseToCurrency;
        }
        return $amounts;
    }

    private function calcFromCurToBase($cur, $amount, $rate) {
        if ($cur == company()->Currency) return $amount;
        return round($this->convertAmt($cur->Method, $amount, $rate), $cur->Decimal);
    }

    private function calcFromBaseToCur($cur, $amount, $rate) {        
        if ($cur == company()->Currency) return $amount;
        if ($cur->Method == 2) $method = 1; else $method = 2;
        return round($this->convertAmt($method, $amount, $rate), $cur->Decimal);
    }

    public function convertAmt($method,$amount,$rate) 
    {
        switch ($method) {
            case 0: return $amount;
            case 1: return $rate * $amount;
            case 2: return $amount / $rate;
        }
    }
    
    public function toBaseAmount($amount, $rate)
    {
        $currency = Currency::findOrFail(company()->Currency);
        switch ($this->Method) {
            case 0: return $currency->round($amount);
            case 1: return $currency->round($amount * $rate);
            case 2: return $currency->round($amount / $rate);
        }
        return $this->round($amount);
    }
    
    public function convertRateSale($currency, $amount, $date = null)
    {
        if ($amount == 0) return $amount;
        if ($currency instanceof Currency) {
            if ($currency->Oid == $this->Oid) return $amount; 
        } else {
            if ($currency == $this->Oid) return $amount;
            $currency = Currency::find($currency);
        }

        $rate1 = $this->getRate($date);
        $rate2 = $currency->getRate($date);
        $amount = $amount * $rate1->getRateSale($this->Oid);
        $amount = $amount / $rate2->getRateSale($currency->Oid);
        return $currency->round($amount);
    }
    
    public function convertRatePurchase($currency, $amount, $date = null)
    {
        if ($amount == 0) return $amount;
        if ($currency instanceof Currency) {
            if ($currency->Oid == $this->Oid) return $amount; 
        } else {
            if ($currency == $this->Oid) return $amount;
            $currency = Currency::find($currency);
        }

        $rate1 = $this->getRate($date);
        $rate2 = $currency->getRate($date);
        $amount = $amount * $rate1->getRatePurchase($this->Oid);
        $amount = $amount / $rate2->getRatePurchase($currency->Oid);
        return $currency->round($amount);
    }
}