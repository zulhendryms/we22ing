<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;

class TravelItemOutbound extends BaseModel {
    protected $table = 'trvitemoutbound';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;
    
    public function ItemObj() { return $this->hasOne("App\Core\Master\Entities\Item", "Oid", "Oid"); }
}