<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionOrderItemProcess extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdorderitemprocess';

    
    public function ProductionOrderItemObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionOrderItem", "ProductionOrderItem", "Oid");}
    public function ProductionProcessObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionProcess", "ProductionProcess", "Oid");}
    public function ProductionPriceProcessObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionPriceProcess", "ProductionPriceProcess", "Oid");}

}