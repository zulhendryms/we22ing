<?php

namespace App\Core\Trucking\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TruckingDriverCode extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trcdrivercode';                

    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
}