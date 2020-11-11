<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseDeliveryDetail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trdpurchasedeliverydetail';                
            
public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
public function TaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'Tax', 'Oid'); }
public function PurchaseDeliveryObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseDelivery', 'PurchaseDelivery', 'Oid'); }
public function ItemUnitObj() { return $this->belongsTo('App\Core\Master\Entities\ItemUnit', 'ItemUnit', 'Oid'); }
public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
public function PurchaseOrderDetailObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrderDetail', 'PurchaseOrderDetail', 'Oid'); }

            

}
        