<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;

class ItemDetailLink extends BaseModel {
    protected $table = 'mstitemdetail';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;
    
    public function ItemObj() { return $this->hasOne("App\Core\Master\Entities\Item", "Oid", "Oid"); }
    public function ItemParentObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "Parent", "Oid"); }
}