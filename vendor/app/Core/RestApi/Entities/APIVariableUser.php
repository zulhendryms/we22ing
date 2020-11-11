<?php

namespace App\Core\RestApi\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class APIVariableUser extends BaseModel {
    use BelongsToCompany;
    protected $table = 'apivariableuser';

    public function APIVariableObj() { return $this->belongsTo("App\Core\RestApi\Entities\APIVariable", "APIVariable", "Oid"); }
    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
}