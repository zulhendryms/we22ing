<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ItemPackage extends BaseModel 
{
        use BelongsToCompany;
        protected $table = 'mstitempackage';                
            
public function itemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'item', 'Oid'); }
public function ItemParentObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'ItemParent', 'Oid'); }   

}