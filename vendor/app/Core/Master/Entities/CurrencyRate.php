<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class CurrencyRate extends BaseModel {
    use BelongsToCompany;
    protected $table = 'mstcurrencyrate';    

    public function CompanyObj() { return $this->belongsTo('App\Core\Master\Entities\Company', 'Company', 'Oid'); }
    public function CurrencyRateDateObj() { return $this->belongsTo('App\Core\Master\Entities\CurrencyRateDate', 'CurrencyRateDate', 'Oid'); }
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    public function Journals() { return $this->hasMany('App\Core\Accounting\Entities\Journal', 'CurrencyRate', 'Oid'); }

    public function convertAmount($method,$amount) 
    {
        switch ($method) {
            case 0: return $amount;
            case 1: return $this->MidRate * $amount;
            case 2: return $amount / $this->MidRate;
        }
    }
    
    // public function toBaseAmount($amount)
    // {
    //     return $this->MidRate * $amount;
    // }

    // public function toSellPrice($amount)
    // {
    //     return $amount / $this->MidRate;
    // }

    public function getRateSale()
    {
        if ($this->Currency == company()->Currency)
            return $this->SellRate;
        else
            return $this->BuyRate;
    }

    public function getRatePurchase($currency)
    {
        if ($this->Currency == company()->Currency)
            return $this->BuyRate;
        else
            return $this->SellRate;
    }
}