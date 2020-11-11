<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ItemAddOnDetail extends BaseModel {
    protected $table = 'mstitemaddondetail';

    public function ItemAddOnObj() { return $this->belongsTo("App\Core\Master\Entities\ItemAddOn", "ItemAddOn", "Oid"); }
    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item","Item", "Oid"); }
    
}