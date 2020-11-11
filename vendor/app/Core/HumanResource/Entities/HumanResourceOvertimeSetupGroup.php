<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceOvertimeSetupGroup extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'hrsovertimesetupgroup';
}
