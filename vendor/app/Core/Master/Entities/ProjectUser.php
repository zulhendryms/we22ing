<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class ProjectUser extends BaseModel {
    use BelongsToCompany;
    protected $table = 'mstprojectuser';
    public function ProjectObj() { return $this->belongsTo("App\Core\Master\Entities\Project", "Project", "Oid"); }
    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
}