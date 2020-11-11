<?php

namespace App\Core\Security\Entities;

use App\Core\Base\Entities\BaseModel;

class NotificationUser extends BaseModel {
    protected $table = 'notificationuser';
    protected $author = false;
    public $timestamps = false;
    protected $gcrecord = false;
    public function NotificationObj() { return $this->belongsTo("App\Core\Security\Entities\Notification", "Notification", "Oid"); }
    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
}