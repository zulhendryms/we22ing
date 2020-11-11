<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceAttendance extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsattendance';

public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
public function AttendanceStatusObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceAttendanceStatus', 'AttendanceStatus', 'Oid'); }
public function AttendanceGroupObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceAttendanceGroup', 'AttendanceGroup', 'Oid'); }
public function AttendanceGroupPatternObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceAttendanceGroupPattern', 'AttendanceGroupPattern', 'Oid'); }
public function AttendanceGroupShiftObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceAttendanceGroupShift', 'AttendanceGroupShift', 'Oid'); }
public function LeaveRequestObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceLeaveRequest', 'LeaveRequest', 'Oid'); }
public function ShiftRequestObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceShiftRequest', 'ShiftRequest', 'Oid'); }
public function OvertimeRequestObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceOvertimeRequest', 'OvertimeRequest', 'Oid'); }


public function Salarys() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceAttendanceSalary', 'HumanResourceAttendance', 'Oid'); }
public function Rawtimes() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceAttendanceRawtime', 'HumanResourceAttendance', 'Oid'); }

}