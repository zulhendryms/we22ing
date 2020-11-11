<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class ModulesGroup extends BaseModel {
    protected $table = 'sysmodulesgroup';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;
}