<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionOrderItemPicture extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdorderitempicture';

    
    public function ProductionOrderItemObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionOrderItem", "ProductionOrderItem", "Oid");}

}