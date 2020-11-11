<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceOvertimeRequest extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsovertimerequest';                
            
public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }

}