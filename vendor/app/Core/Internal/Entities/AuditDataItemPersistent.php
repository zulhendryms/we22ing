<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class AuditDataItemPersistent extends BaseModel {
    protected $table = 'auditdataitempersistent';
    protected $author = false;
    public $timestamps = false;
}