<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceEmployeeGroup extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsemployeegroup';                
            
public function JobPositionObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceJobPosition', 'JobPosition', 'Oid'); }
public function DepartmentObj() { return $this->belongsTo('App\Core\Master\Entities\Department', 'Department', 'Oid'); }
}