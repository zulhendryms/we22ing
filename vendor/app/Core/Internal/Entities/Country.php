<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;

class Country extends BaseModel {

    use Activable;
    protected $table = 'syscountry';

    public function __get($property) 
    {
        switch($property) {
            case "Title": return $this->Name.' - '.$this->Code;
            case 'IsIndonesia': return $this->Code == 'ID';
        }
        return parent::__get($property);
    }

    public function FerryCountryObj() { return $this->hasOne("App\Core\Ferry\Entities\FerryCountry", "Oid", "Oid"); }
    public function scopeIndonesia() { return $this->where('Code', 'ID')->first(); }
}