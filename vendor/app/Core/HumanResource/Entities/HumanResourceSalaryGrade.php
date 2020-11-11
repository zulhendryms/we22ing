<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceSalaryGrade extends BaseModel 
{
use BelongsToCompany;
protected $table = 'hrssalarygrade';                

public function Details() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceSalaryGradeDetail', 'HumanResourceSalaryGrade', 'Oid'); }

}