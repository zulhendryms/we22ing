<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceEmployeeMutation extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsemployeemutation';                
            
public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
public function DepartmentObj() { return $this->belongsTo('App\Core\Master\Entities\Department', 'Department', 'Oid'); }
public function JobTitleObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceJobTitle', 'JobTitle', 'Oid'); }
public function JobPositionObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceJobPosition', 'JobPosition', 'Oid'); }

}