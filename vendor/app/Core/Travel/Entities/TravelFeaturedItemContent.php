<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelFeaturedItemContent extends BaseModel 
{
        use BelongsToCompany;
        protected $table = 'trvfeatureditemcontent';                
            
public function ItemContentObj() { return $this->belongsTo('App\Core\Master\Entities\ItemContent', 'ItemContent', 'Oid'); }
}