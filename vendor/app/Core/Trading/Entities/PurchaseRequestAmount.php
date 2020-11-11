<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseRequestAmount extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trdpurchaserequestamount';

    public function PurchaseRequestObj()
    {
        return $this->belongsTo('App\Core\Trading\Entities\PurchaseRequest', 'PurchaseRequest', 'Oid');
    }
}
