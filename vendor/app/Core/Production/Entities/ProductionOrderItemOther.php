<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionOrderItemOther extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdorderitemother';

    
    public function ProductionOrderItemObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionOrderItem", "ProductionOrderItem", "Oid");}
    public function ItemObj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");}
}