<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelItemTourPackageOtherAmount extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvitemtourpackageotheramount';                
            
    public function ItemTourPackageObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelItemTourPackage', 'ItemTourPackage', 'Oid'); }

}
        