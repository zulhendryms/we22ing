<?php

namespace App\Core\Apitude\Entities;

use App\Core\Base\Entities\BaseModel;

class HotelImage extends BaseModel {
    protected $table = 'apitudeapi_image';
    public $timestamps = false;
    protected $author = false;
    protected $gcrecord = false;

    public function HotelObj() { return $this->belongsTo("App\Core\Apitude\Entities\Hotel", "hotel_code", "code"); }
}