<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceShiftRequest extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsshiftrequest';                
            
public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
public function ShiftObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceShift', 'Shift', 'Oid'); }

}