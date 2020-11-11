<?php

namespace App\Core\RestApi\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class CommandGroup extends BaseModel {
    use BelongsToCompany;
    protected $table = 'apicommandgroup';
    public function ProjectObj() { return $this->belongsTo("App\Core\Master\Entities\Project", "Project", "Oid"); }
    public function Users() { return $this->hasMany("App\Core\RestApi\Entities\CommandGroupUser", "CommandGroup", "Oid"); }
    public function Commands() { return $this->hasMany("App\Core\RestApi\Entities\Command", "CommandGroup", "Oid"); }
}