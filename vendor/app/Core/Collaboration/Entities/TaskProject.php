<?php

namespace App\Core\Collaboration\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TaskProject extends BaseModel {
    use BelongsToCompany;
    protected $table = 'coltaskproject';

    public function TaskObj() { return $this->belongsTo("App\Core\Collaboration\Entities\Task", "Task", "Oid"); }
    public function ProjectObj() { return $this->belongsTo("App\Core\Master\Entities\Project", "Project", "Oid"); }
}