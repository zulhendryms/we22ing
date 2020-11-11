<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionUnitConvertion extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdunitconvertion';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function Details() { return $this->hasMany("App\Core\Production\Entities\ProductionUnitConvertionDetail", "ProductionUnitConvertion","Oid"); }
}