<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionPriceDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdpricedetail';

    
    public function ProductionPriceObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionPrice", "ProductionPrice", "Oid");}

}