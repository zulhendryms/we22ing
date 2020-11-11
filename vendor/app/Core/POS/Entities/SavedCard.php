<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class SavedCard extends BaseModel {
    protected $table = 'possavedcard';
    use BelongsToCompany;

    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
}