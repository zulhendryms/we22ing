<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionItemProcess extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prditemprocess';

    /**
     * Get the item of detail
     */
    public function ItemObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");
    }

    /**
     * Get the pos of detail
     */
    public function ProductionProcessObj()
    {
        return $this->belongsTo("App\Core\Production\Entities\ProductionProcess", "ProductionProcess", "Oid");
    }

    public function ProductionPriceObj()
    {
        return $this->belongsTo("App\Core\Production\Entities\ProductionPrice", "ProductionPrice", "Oid");
    }

   

}