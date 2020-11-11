<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionPriceProcess extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdpriceprocess';

    
    public function ProductionProcessObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionProcess", "ProductionProcess", "Oid");}
    public function Details() { return $this->hasMany("App\Core\Production\Entities\ProductionPriceProcessDetail", "ProductionPriceProcess","Oid"); }

}