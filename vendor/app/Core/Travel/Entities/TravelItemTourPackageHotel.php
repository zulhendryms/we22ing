<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelItemTourPackageHotel extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvitemtourpackagehotel';                
            
    public function ItemTourPackageObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelItemTourPackage', 'ItemTourPackage', 'Oid'); }
    public function ItemContentObj() { return $this->belongsTo('App\Core\Master\Entities\ItemContent', 'ItemContent', 'Oid'); }
    public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
}
        