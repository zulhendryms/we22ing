<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelItemTourPackageItinerary extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvitemtourpackageitinerary';                
            
    public function TravelItemTourPackageObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelItemTourPackage', 'TravelItemTourPackage', 'Oid'); }

}
        