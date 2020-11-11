<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class HotelECommerce extends BaseModel {
    use Activable, BelongsToCompany;
    protected $table = 'mstitemecommercehotel';

    public function ECommerceObj() { return $this->belongsTo('App\Core\Master\Entities\ECommerce', 'ECommerce', 'Oid'); }
    
}