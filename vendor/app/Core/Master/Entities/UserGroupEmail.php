<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class UserGroupEmail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstusergroupemail';

public function Details() { return $this->hasMany('App\Core\Master\Entities\UserGroupEmailDetail', 'UserGroupEmail', 'Oid'); }

}