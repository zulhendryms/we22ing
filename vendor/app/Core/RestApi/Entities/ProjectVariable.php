<?php

namespace App\Core\RestApi\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProjectVariable extends BaseModel {
    use BelongsToCompany;
    protected $table = 'apiprojectvariable';

    public function ProjectObj() { return $this->belongsTo("App\Core\Master\Entities\Project", "Project", "Oid"); }
    // public function Details() { return $this->hasMany("App\Core\RestAPI\Entities\ProjectVariable", "Project", "Oid"); }
}