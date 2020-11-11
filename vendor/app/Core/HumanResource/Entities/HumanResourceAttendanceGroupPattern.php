<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceAttendanceGroupPattern extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsattendancegrouppattern';                
            
    public function HumanResourceAttendanceGroupObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceAttendanceGroup', 'HumanResourceAttendanceGroup', 'Oid'); }

}