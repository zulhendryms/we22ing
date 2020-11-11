<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class Modules extends BaseModel {
    protected $table = 'sysmodules';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;

    public function ModulesParentObj() { return $this->belongsTo("App\Core\Internal\Entities\ModulesParent", "ModulesParent", "Oid"); }
}