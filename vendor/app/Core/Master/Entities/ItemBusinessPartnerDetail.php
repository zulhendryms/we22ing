<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ItemBusinessPartnerDetail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstitembusinesspartnerdetail';
    
public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
public function ItemBusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\ItemBusinessPartner', 'ItemBusinessPartner', 'Oid'); }

    

}
