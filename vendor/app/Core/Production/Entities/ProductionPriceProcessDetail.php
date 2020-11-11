<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionPriceProcessDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdpriceprocessdetail';

    
    public function ProductionPriceProcessObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionPriceProcess", "ProductionPriceProcess", "Oid");}

}