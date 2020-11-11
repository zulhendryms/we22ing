<?php

namespace App\Core\Chat\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ChatRoomUser extends BaseModel {
    use BelongsToCompany;
    protected $table = 'chatroomuser';
    protected $gcrecord = false;

    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
    public function CreatedByObj() { return $this->belongsTo("App\Core\Security\Entities\User", "CreatedBy", "Oid"); }
    public function UpdatedByObj() { return $this->belongsTo("App\Core\Security\Entities\User", "UpdatedBy", "Oid"); }
    public function ChatRoomObj() { return $this->belongsTo("App\Core\Chat\Entities\ChatRoom", "ChatRoom", "Oid"); }
}