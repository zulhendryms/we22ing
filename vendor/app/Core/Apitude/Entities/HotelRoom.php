<?php

namespace App\Core\Apitude\Entities;

use App\Core\Base\Entities\BaseModel;

class HotelRoom extends BaseModel {
    protected $table = 'apitudeapi_hotel_room';
    protected $gcrecord = false;

    public function HotelObj() { return $this->belongsTo("App\Core\Apitude\Entities\Hotel", "hotel_code", "code"); }
    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "mstitem_oid", "Oid"); }
}