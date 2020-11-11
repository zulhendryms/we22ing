<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceAttendanceGroup extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsattendancegroup';

    public function Shifts() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceAttendanceGroupShift', 'HumanResourceAttendanceGroup', 'Oid'); }
    public function Patterns() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceAttendanceGroupPattern', 'HumanResourceAttendanceGroup', 'Oid'); }
    public function Overtimes() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceAttendanceGroupOvertime', 'HumanResourceAttendanceGroup', 'Oid'); }

}