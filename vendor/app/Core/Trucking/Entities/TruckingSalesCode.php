<?php

namespace App\Core\Trucking\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TruckingSalesCode extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trcsalescode';                
    
public function TruckingDriverCodeObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingDriverCode', 'TruckingDriverCode', 'Oid'); }

public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    

}