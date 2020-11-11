<?php

namespace App\Core\RestApi\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Command extends BaseModel {
    use BelongsToCompany;
    protected $table = 'apicommand';

    public function CommandGroupObj() { return $this->belongsTo("App\Core\RestApi\Entities\CommandGroup", "CommandGroup", "Oid"); }
    // public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
    public function Params() { return $this->hasMany("App\Core\RestApi\Entities\CommandParam", "APICommand", "Oid"); }
}