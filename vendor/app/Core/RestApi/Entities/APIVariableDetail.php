<?php

namespace App\Core\RestApi\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class APIVariableDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'apivariabledetail';

    public function APIVariableObj() { return $this->belongsTo("App\Core\RestApi\Entities\APIVariable", "APIVariable", "Oid"); }
}