<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class FeatureInfo extends BaseModel {
    use BelongsToCompany;
    protected $table = 'posfeatureinfo';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function Items() { return $this->hasMany("App\Core\POS\Entities\FeatureInfoItem", "POSFeatureInfo", "Oid"); }

}