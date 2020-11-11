<?php

namespace App\Core\Chat\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class ChatRoom extends BaseModel {
    use Activable, BelongsToCompany;
    protected $table = 'chatroom';
    protected $gcrecord = false;

    public function Companybj() { return $this->belongsTo("App\Core\Master\Entities\Company", "User", "Oid"); }
    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
    public function UserAdminObj() { return $this->belongsTo("App\Core\Security\Entities\User", "UserAdmin", "Oid"); }
    public function Details() { return $this->hasMany("App\Core\Chat\Entities\ChatMessage", "ChatRoom", "Oid"); }
    public function Users() { return $this->hasMany("App\Core\Chat\Entities\ChatRoomUser", "ChatRoom", "Oid"); }
}