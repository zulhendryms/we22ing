<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;

class Module extends BaseModel {
    use Activable;
    protected $table = 'sysmodules';
}