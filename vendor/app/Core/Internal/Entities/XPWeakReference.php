<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class XPWeakReference extends BaseModel {
    protected $table = 'xpweakreference';
    protected $author = false;
    public $timestamps = false;

    public function AuditedObjectWeakReferenceObj()
    {
        return $this->hasOne('App\Core\Internal\Entities\AuditedObjectWeakReference', 'Oid', 'Oid');
    }
}