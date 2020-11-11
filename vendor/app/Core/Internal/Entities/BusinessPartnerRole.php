<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class BusinessPartnerRole extends BaseModel
{
    protected $table = 'sysbusinesspartnerrole';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
}