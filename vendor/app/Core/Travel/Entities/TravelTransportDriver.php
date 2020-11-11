<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelTransportDriver extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvtransportdriver';
    
    public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }

}