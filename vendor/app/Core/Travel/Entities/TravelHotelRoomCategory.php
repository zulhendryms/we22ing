<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelHotelRoomCategory extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trvhotelroomcategory';
}
