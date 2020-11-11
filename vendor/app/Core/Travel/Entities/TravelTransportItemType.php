<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelTransportItemType extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvtransportitemtype';
    // public function TravelTransportItemTypeObj() { return $this->belongsTo("App\Core\Travel\Entities\TravelTransportItemType", "TravelTransportItemType", "Oid");}
}