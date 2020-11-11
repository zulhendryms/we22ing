<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelPackage extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvpackage';

    public function TravelTypeObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelType', 'TravelType', 'Oid'); }
    public function CountryObj() { return $this->belongsTo('App\Core\Internal\Entities\Country', 'Country', 'Oid'); }        
    public function Details() { return $this->hasMany('App\Core\Travel\Entities\TravelPackageDetail', 'TravelPackage', 'Oid'); }
}