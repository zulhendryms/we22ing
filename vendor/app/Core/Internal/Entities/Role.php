<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Role extends BaseModel {
    use BelongsToCompany;
    protected $table = 'role';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;
    // public function Modules()
    // {
    //     return $this
    //         ->belongsToMany('App\Core\Internal\Entities\Modules', 'rolemodules', 'Role', 'Modules')
    //         ->as('Modules')
    //         ->withPivot('Oid', 'IsRead', 'IsAdd', 'IsEdit', 'IsDelete');
    // }   
    // public function ModulesCustom()
    // {
    //     return $this
    //         ->belongsToMany('App\Core\Internal\Entities\Modules', 'rolemodulescustom', 'Role', 'Oid')
    //         ->as('CustomModules')
    //         ->withPivot('Oid', 'IsEnable');
    // }   
    public function Modules() { return $this->hasMany("App\Core\Internal\Entities\RoleModule", "Role", "Oid"); }
    public function ModulesCustom() { return $this->hasMany("App\Core\Internal\Entities\RoleModule;Custom", "Role", "Oid"); }
}