<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class EmployeeRole extends BaseModel 
{
    protected $table = 'sysemployeerole'; //test

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
}