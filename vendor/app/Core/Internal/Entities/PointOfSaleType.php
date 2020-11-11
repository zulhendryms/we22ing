<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

/**
 * @property-read boolean $IsFerry
 * @property-read boolean $IsAttraction
 */
class PointOfSaleType extends BaseModel {
    protected $table = 'syspointofsaletype';

    public function __get($key)
    {
        switch ($key) {
            case "Title": return $this->Name.' - '.$this->Code;
            case "IsFerry":  return $this->Code == "ferry";
            case "IsAttraction": return $this->Code == "attraction";
        }
        return parent::__get($key);
    }
    
    public function scopeFerry() { return $this->where('Code', 'ferry')->first(); }
    public function scopeAttraction() { return $this->where('Code', 'attraction')->first(); }
    public function scopeToken() { return $this->where('Code', 'TOKEN')->first(); }
}