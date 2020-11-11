<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class AuditedObjectWeakReference extends BaseModel {
    protected $table = 'auditedobjectweakreference';
    protected $author = false;
    protected $gcrecord = false;
    public $timestamps = false;
}