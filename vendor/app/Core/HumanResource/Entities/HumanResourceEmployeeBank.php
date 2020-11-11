<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceEmployeeBank extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsemployeebank';                
            
public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
public function BankObj() { return $this->belongsTo('App\Core\Master\Entities\Bank', 'Bank', 'Oid'); }

}