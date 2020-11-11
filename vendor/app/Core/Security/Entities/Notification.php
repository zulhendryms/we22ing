<?php

namespace App\Core\Security\Entities;

use App\Core\Base\Entities\BaseModel;

class Notification extends BaseModel {
    protected $table = 'notification';
    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
    public function CreatedByObj() { return $this->belongsTo("App\Core\Security\Entities\User", "CreatedBy", "Oid"); }
    public function CompanyObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "Company", "Oid"); }
    public function Details() { return $this->hasMany("App\Core\Security\Entities\NotificationUser", "Notification", "Oid"); }
    public function Users() { return $this->hasMany("App\Core\Security\Entities\User", "User", "Oid"); }
}