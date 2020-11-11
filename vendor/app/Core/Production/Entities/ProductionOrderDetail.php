<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionOrderDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdorderdetail';

    
    public function ProductionOrderObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionOrder", "ProductionOrder", "Oid");}
    public function ItemObj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");}

}