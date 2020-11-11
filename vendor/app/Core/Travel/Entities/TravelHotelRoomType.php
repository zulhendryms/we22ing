<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelHotelRoomType extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvhotelroomtype';
}