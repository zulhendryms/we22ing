<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceSalaryGradeDetail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrssalarygradedetail';                
            
    public function HumanResourceSalaryGradeObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceSalaryGrade', 'HumanResourceSalaryGrade', 'Oid'); }
    public function SalaryItemObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceSalaryItem', 'SalaryItem', 'Oid'); }
    
}