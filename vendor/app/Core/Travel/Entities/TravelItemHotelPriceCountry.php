<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelItemHotelPriceCountry extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trvitemhotelpricecountry';

    public function TravelItemHotelPriceObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelItemHotelPrice', 'TravelItemHotelPrice', 'Oid');
    }
    public function CountryObj()
    {
        return $this->belongsTo('App\Core\Internal\Entities\Country', 'Country', 'Oid');
    }
}
