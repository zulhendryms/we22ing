<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelPassenger extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvtransactionpassenger';

    public function TravelTransactionDetailObj() { return $this->belongsTo("App\Core\Travel\Entities\TravelTransactionDetail", "TravelTransactionDetail", "Oid"); }
}