<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelTransactionItinerary extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvtransactionitinerary';                
    
    public function TravelTransactionObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelTransaction', 'TravelTransaction', 'Oid'); }
    public function BusinessPartnerHotelObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartnerHotel', 'Oid'); }

    

}