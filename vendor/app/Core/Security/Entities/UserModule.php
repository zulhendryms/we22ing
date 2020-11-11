<?php

namespace App\Core\Security\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class UserModule extends BaseModel {
    use BelongsToCompany;
    protected $table = 'usermodule';

    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
    public function ModulesObj() { return $this->belongsTo("App\Core\Internal\Entities\Modules", "Modules", "Code"); }

}