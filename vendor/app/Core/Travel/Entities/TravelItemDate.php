<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelItemDate extends BaseModel {
    protected $table = 'trvitemdate';
    use BelongsToCompany;

    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid"); }
}