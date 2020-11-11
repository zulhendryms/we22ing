<?php

namespace App\Core\RestApi\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class CommandGroupUser extends BaseModel {
    use BelongsToCompany;
    protected $table = 'apicommandgroupuser';
    public function CommandGroupObj() { return $this->belongsTo("App\Core\RestApi\Entities\CommandGroup", "CommandGroup", "Oid"); }
    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }

}