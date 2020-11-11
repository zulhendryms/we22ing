<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class CompanyType extends BaseModel 
{
    protected $table = 'syscompanytype';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function CompanyObj() { return $this->hasMany('App\Core\Master\Entities\Company', 'CompanyType', 'Oid'); }
}