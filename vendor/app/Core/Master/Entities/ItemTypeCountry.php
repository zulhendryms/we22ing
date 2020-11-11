<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class ItemTypeCountry extends BaseModel {
    use Activable, BelongsToCompany;
    protected $table = 'mstitemtypecountry';

    // public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
    public function CountryObj() { return $this->belongsTo('App\Core\Internal\Entities\Country', 'Country', 'Oid'); }
    
}