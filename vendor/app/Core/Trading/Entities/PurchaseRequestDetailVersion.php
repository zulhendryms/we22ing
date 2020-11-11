<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseRequestDetailVersion extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trdpurchaserequestdetailversion';                
    
public function PurchaseRequestObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseRequestDetailVersion', 'PurchaseRequest', 'Oid'); }
public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }

    

}