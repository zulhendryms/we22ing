<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;

class TravelItem extends BaseModel {
    protected $table = 'trvitem';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;

    public function ItemObj() { return $this->hasOne("App\Core\Master\Entities\Item", "Oid", "Oid"); }
}