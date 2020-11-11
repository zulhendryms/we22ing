<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class ItemType extends BaseModel {
    protected $table = 'sysitemtype';

    public function __get($property) 
    {
        switch($property) {
            case "Title": return $this->Name.' - '.$this->Code;
            case 'IsTour': return $this->Code == 'Travel';
            case 'IsHotel': return $this->Code == 'Hotel';
            case 'IsProduct': return $this->Code == 'Product';
            case 'IsTransport': return $this->Code == 'Transport';
            case 'IsService': return $this->Code == 'Service';
        }
        return parent::__get($property);
    }
    public function ItemTypeCountries() { return $this->hasMany("App\Core\Master\Entities\ItemTypeCountry", "ItemType","Oid"); }

    public function ItemTypePriceMethodObj() { return $this->hasOne("App\Core\Master\Entities\ItemTypePriceMethod", "Oid", "ItemTypePriceMethod"); }
}