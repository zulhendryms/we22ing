<?php

namespace App\Core\Apitude\Entities;

use App\Core\Base\Entities\BaseModel;

class Hotel extends BaseModel {
    protected $table = 'apitudeapi_hotel';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;
    protected $primaryKey = 'code';

    public function Images() { return $this->hasMany("App\Core\Apitude\Entities\HotelImage", "hotel_code", "code"); }
    public function Rooms() { return $this->hasMany("App\Core\Apitude\Entities\HotelRoom", "hotel_code", "code"); }
    public function HotelECommerces() { return $this->hasMany("App\Core\Master\Entities\HotelECommerce", "HotelCode","code"); }
}