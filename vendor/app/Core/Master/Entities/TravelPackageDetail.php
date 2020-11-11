<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;

class TravelPackageDetail extends BaseModel {
    protected $table = 'trvpackagedetail';

    public function ParentObj() { return $this->belongsTo("App\Core\Master\Entities\TravelPackage", "TravelPackage", "Oid"); }
}