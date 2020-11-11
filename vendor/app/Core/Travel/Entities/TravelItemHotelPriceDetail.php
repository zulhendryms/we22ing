<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelItemHotelPriceDetail extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trvitemhotelpricedetail';

    public function TravelItemHotelPriceObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelItemHotelPrice', 'TravelItemHotelPrice', 'Oid');
    }
}
