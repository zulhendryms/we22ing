<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class RoleModuleCustom extends BaseModel {
    protected $table = 'rolemodulescustom';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;

    public function RoleObj() { return $this->belongsTo("App\Core\Internal\Entities\Role", "Role", "Oid"); }
    public function ModulesObj() { return $this->belongsTo("App\Core\Internal\Entities\Modules", "Modules", "Code"); }
}