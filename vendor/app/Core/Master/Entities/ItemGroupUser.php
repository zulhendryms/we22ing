<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ItemGroupUser extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstitemgroupuser';                
            
public function ItemGroupObj() { return $this->belongsTo('App\Core\Master\Entities\ItemGroup', 'ItemGroup', 'Oid'); }
public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }

}
        