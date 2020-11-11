<?php

namespace App\Core\RestApi\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class APIVariable extends BaseModel {
    use BelongsToCompany;
    protected $table = 'apivariable';

    public function Details() { return $this->hasMany("App\Core\RestApi\Entities\APIVariableDetail", "APIVariable", "Oid"); }
    public function Users() { return $this->hasMany("App\Core\RestApi\Entities\APIVariableUser", "APIVariable", "Oid"); }
}