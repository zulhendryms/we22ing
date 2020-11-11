<?php

namespace App\Core\Security\Entities;

use App\Core\Base\Entities\BaseModel;

class RoleMaster extends BaseModel {
    protected $table = 'permissionpolicyrole';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;

}