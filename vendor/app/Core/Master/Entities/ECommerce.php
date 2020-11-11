<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ECommerce extends BaseModel {
    use BelongsToCompany;
    protected $table = 'mstecommerce';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    public function Details() { return $this->hasMany('App\Core\Master\Entities\ECommerceField', 'ECommerce', 'Oid'); }
}