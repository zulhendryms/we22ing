<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PointOfSaleDetailItem extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'pospointofsaledetailitem';

    /**
     * Get the item parent of detail item
     */
    public function ItemParentObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Item", "ItemParent", "Oid");
    }

    /**
     * Get the item parent of detail item
     */
    public function ItemObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");
    }

    /**
     * Get the pos of detail item
     */
    public function PointOfSaleObj()
    {
        return $this->belongsTo("App\Core\POS\Entities\PointOfSale", "PointOfSale", "Oid");
    }
}