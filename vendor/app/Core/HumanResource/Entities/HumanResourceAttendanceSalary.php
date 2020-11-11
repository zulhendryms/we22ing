<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceAttendanceSalary extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsattendancesalary';                
            
    public function HumanResourceAttendanceObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceAttendance', 'HumanResourceAttendance', 'Oid'); }
    public function SalaryItemObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceSalaryItem', 'SalaryItem', 'Oid'); }
    
}