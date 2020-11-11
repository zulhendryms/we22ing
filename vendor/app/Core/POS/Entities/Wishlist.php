<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Wishlist extends BaseModel {
    use BelongsToCompany;
    protected $table = 'poswishlist';

    public function ItemObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");
    }

    public function UserObj()
    {
        return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid");
    }
}