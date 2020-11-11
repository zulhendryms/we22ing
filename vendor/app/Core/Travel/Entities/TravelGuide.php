<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelGuide extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvguide';                
            
public function TravelGuideGroupObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelGuideGroup', 'TravelGuideGroup', 'Oid'); }
public function PaymentTermObj() { return $this->belongsTo('App\Core\Master\Entities\PaymentTerm', 'PaymentTerm', 'Oid'); }
public function CountryObj() { return $this->belongsTo('App\Core\Internal\Entities\Country', 'Country', 'Oid'); }

            

}
        