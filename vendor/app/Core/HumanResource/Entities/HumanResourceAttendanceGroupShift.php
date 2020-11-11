<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceAttendanceGroupShift extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsattendancegroupshift';                
            
    public function HumanResourceAttendanceGroupObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceAttendanceGroup', 'HumanResourceAttendanceGroup', 'Oid'); }

}