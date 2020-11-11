<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelTransactionAllotment extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvtransactionallotment';

    /**
     * Get sales transaction detail of the allotment transaction
     */
    public function TravelSalesTransactionDetailObj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelSalesTransactionDetail", "TravelSalesTransactionDetail", "Oid");
    }

    public function TravelAllotmentObj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelAllotment", "TravelAllotment", "Oid");
    }

    public function ItemObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");
    }
}