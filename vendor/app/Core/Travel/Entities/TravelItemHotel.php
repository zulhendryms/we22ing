<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;

class TravelItemHotel extends BaseModel {
    protected $table = 'trvitemhotel';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;
    
    public function ItemObj() { return $this->hasOne("App\Core\Master\Entities\Item", "Oid", "Oid"); }
    public function TravelHotelRoomTypeObj() { return $this->belongsTo("App\Core\Travel\Entities\TravelHotelRoomType", "TravelHotelRoomType", "Oid"); }
}