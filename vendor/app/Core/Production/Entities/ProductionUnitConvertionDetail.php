<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionUnitConvertionDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdunitconvertiondetail';

    
    public function ProductionUnitConvertionObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionUnitConvertion", "ProductionUnitConvertion", "Oid");}

}