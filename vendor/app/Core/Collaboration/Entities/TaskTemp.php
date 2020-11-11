<?php

namespace App\Core\Collaboration\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TaskTemp extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'coltasktemp';

    public function TaskTempObj()
    {
        return $this->belongsTo('App\Core\Collaboration\Entities\TaskTemp', 'TaskTemp', 'Oid');
    }
}
