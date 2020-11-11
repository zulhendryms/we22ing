<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;

class TravelPackage extends BaseModel {
    use Activable;
    protected $table = 'trvpackage';

    public function Details() { return $this->hasMany("App\Core\Master\Entities\TravelPackageDetail", "TravelPackage", "Oid"); }
}