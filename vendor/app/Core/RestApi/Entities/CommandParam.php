<?php

namespace App\Core\RestApi\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class CommandParam extends BaseModel {
    use BelongsToCompany;
    protected $table = 'apicommandparam';

    public function CommandObj() { return $this->belongsTo("App\Core\RestApi\Entities\Command", "APICommand", "Oid"); }
}