<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelTransactionFlight extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvtransactionflight';                
    
    public function TravelTransactionObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelTransaction', 'TravelTransaction', 'Oid'); }
    public function TravelFlightNumberObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelFlightNumber', 'TravelFlightNumber', 'Oid'); }

    

}