<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceLeaveGroup extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'hrsleavegroup';
}
