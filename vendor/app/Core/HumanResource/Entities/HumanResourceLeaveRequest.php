<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceLeaveRequest extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsleaverequest';                
            
public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
public function AttendanceStatusObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceAttendanceStatus', 'AttendanceStatus', 'Oid'); }

}