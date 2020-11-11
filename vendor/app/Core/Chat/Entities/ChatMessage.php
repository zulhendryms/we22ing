<?php

namespace App\Core\Chat\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ChatMessage extends BaseModel {
    use BelongsToCompany;
    protected $table = 'chatmessage';
    protected $gcrecord = false;

    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
    public function ChatRoomObj() { return $this->belongsTo("App\Core\Chat\Entities\ChatRoom", "ChatRoom", "Oid"); }
}