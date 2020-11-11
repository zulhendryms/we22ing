<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class EmployeeContact extends BaseModel {
    protected $table = 'mstemployeecontact';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function EmployeeObj() { return $this->belongsTo("App\Core\Master\Entities\Employee", "Employee", "Oid"); }
}