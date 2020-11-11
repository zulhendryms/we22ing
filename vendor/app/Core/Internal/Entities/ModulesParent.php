<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class ModulesParent extends BaseModel {
    protected $table = 'sysmodulesparent';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;
}