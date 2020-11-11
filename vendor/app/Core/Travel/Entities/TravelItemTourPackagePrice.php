<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelItemTourPackagePrice extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvitemtourpackageprice';                
            
    public function ItemTourPackageObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelItemTourPackage', 'ItemTourPackage', 'Oid'); }
    public function CountryObj() { return $this->belongsTo('App\Core\Internal\Entities\Country', 'Country', 'Oid'); }

}
        