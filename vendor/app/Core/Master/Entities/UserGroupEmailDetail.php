<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class UserGroupEmailDetail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstusergroupemaildetail';
            
public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
public function UserGroupEmailObj() { return $this->belongsTo('App\Core\Master\Entities\UserGroupEmail', 'UserGroupEmail', 'Oid'); }

        }