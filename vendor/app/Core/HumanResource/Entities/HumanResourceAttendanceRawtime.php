<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceAttendanceRawtime extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsattendancerawtime';

    public function HumanResourceAttendanceObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceAttendance', 'HumanResourceAttendance', 'Oid'); }
}