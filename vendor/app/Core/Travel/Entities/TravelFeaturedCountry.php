<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelFeaturedCountry extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvfeaturedcountry';                
    
public function CountryObj() { return $this->belongsTo('App\Core\Internal\Entities\Country', 'Country', 'Oid'); }
}
        