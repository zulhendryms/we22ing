<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceEmploymentHistory extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsemploymenthistory';

public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
public function EmployeePreviousObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'EmployeePrevious', 'Oid'); }

}