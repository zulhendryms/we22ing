<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceEmployeeContract extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'hrsemployeecontract';
}
