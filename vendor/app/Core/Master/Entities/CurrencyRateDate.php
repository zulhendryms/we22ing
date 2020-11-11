<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class CurrencyRateDate extends BaseModel {
    use BelongsToCompany;
    protected $table = 'mstcurrencyratedate';
    public function Details()  
    {
        return $this->hasMany("App\Core\Master\Entities\CurrencyRate", "CurrencyRateDate", "Oid");
    }
}