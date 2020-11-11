<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelItemHotelPrice extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trvitemhotelprice';

    public function ItemObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid');
    }
    public function CurrencyObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid');
    }
    public function TravelHotelRoomCategoryObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelHotelRoomCategory', 'TravelHotelRoomCategory', 'Oid');
    }


    public function Blacklists()
    {
        return $this->hasMany('App\Core\Travel\Entities\TravelItemHotelPriceCountryBlacklist', 'TravelItemHotelPrice', 'Oid');
    }
    public function Countries()
    {
        return $this->hasMany('App\Core\Travel\Entities\TravelItemHotelPriceCountry', 'TravelItemHotelPrice', 'Oid');
    }
    public function Details()
    {
        return $this->hasMany('App\Core\Travel\Entities\TravelItemHotelPriceDetail', 'TravelItemHotelPrice', 'Oid');
    }
}
