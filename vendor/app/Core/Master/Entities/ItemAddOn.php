<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class ItemAddOn extends BaseModel {
    use Activable, BelongsToCompany;
    protected $table = 'mstitemaddon';

    public function Details() { return $this->hasMany('App\Core\Master\Entities\ItemAddOnDetail', 'ItemAddOn', 'Oid'); }
}