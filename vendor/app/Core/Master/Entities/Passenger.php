<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Passenger extends BaseModel {
    use BelongsToCompany;
    protected $table = 'mstpassenger';
    
    public function CountryObj() { return $this->belongsTo("App\Core\Internal\Entities\Country", "Country", "Oid"); }
    public function NationalityObj() { return $this->belongsTo("App\Core\Internal\Entities\Country", "Nationality", "Oid"); }
    
    public function scopeAdult($query) { return $query->whereRaw('YEAR(NOW()) - YEAR(DateOfBirth) > 12'); }
    public function scopeChild($query) { return $query->whereRaw('YEAR(NOW()) - YEAR(DateOfBirth) > 1 AND YEAR(NOW()) - YEAR(DateOfBirth) <= 12'); }
    public function scopeInfant($query) { return $query->whereRaw('YEAR(NOW()) - YEAR(DateOfBirth) <= 1'); }
}