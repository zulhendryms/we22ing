<?php

namespace App\Core\Collaboration\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TaskLog extends BaseModel {
    use BelongsToCompany;
    protected $table = 'coltasklog';

    public function TaskObj() { return $this->belongsTo("App\Core\Collaboration\Entities\Task", "Task", "Oid"); }
    public function User1Obj() { return $this->belongsTo("App\Core\Security\Entities\User", "User1", "Oid"); }
}