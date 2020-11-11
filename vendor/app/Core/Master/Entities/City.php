<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class City extends BaseModel {
    use Activable, BelongsToCompany;
    // use Activable;
    protected $table = 'mstcity';

    public function __get($key)
    {
        switch ($key) {
            case "Title": return $this->Name.' - '.$this->Code;
            case "CountryName": if (isset($this->Country)) { return $this->CountryObj->Name.' - '.$this->CountryObj->Code; }
        }
        return parent::__get($key);
    }
    
    public function CompanyObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "Company", "Oid"); }
    public function CountryObj() { return $this->belongsTo("App\Core\Internal\Entities\Country", "Country", "Oid"); }
    public function RegionObj() { return $this->belongsTo("App\Core\Master\Entities\Region", "Region","Oid"); }
    public function TimezoneObj() { return $this->belongsTo("App\Core\Internal\Entities\Timezone", "Timezone", "Oid"); }
    public function BusinessPartnerAddresses() { return $this->hasMany("App\Core\Master\Entities\BusinessPartnerAddress", "City", "Oid"); }
}