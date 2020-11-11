<?php

namespace App\Core\Trucking\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TruckingAddress extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trcaddress';

    public function BusinessPartnerObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid');
    }
    public function CityObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\City', 'City', 'Oid');
    }
}
