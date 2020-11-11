<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class ItemECommerce extends BaseModel {
    use Activable, BelongsToCompany;
    protected $table = 'mstitemecommerce';


    public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
    public function ECommerceObj() { return $this->belongsTo('App\Core\Master\Entities\ECommerce', 'ECommerce', 'Oid'); }
    
}