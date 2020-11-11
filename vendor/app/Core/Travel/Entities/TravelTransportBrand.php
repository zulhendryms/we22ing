<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelTransportBrand extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvtransportbrand';
    public function TravelTransportItemTypeObj() { return $this->belongsTo("App\Core\Travel\Entities\TravelTransportItemType", "TravelTransportItemType", "Oid");}
    public function TransportItemTypeObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelTransportItemType', 'TransportItemType', 'Oid'); }

}