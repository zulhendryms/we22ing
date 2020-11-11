<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ErrorLog extends BaseModel {

    use BelongsToCompany;

    protected $table = 'syserrorlog';
    public $incrementing = true;
}