<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelTransportVehicle extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvtransportvehicle';

    public function TravelTransportDriverObj() { return $this->belongsTo("App\Core\Travel\Entities\TravelTransportDriver", "TravelTransportDriver", "Oid"); }
    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "TravelItemTransport", "Oid");}
}