<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelFeaturedCity extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trvfeaturedcity';

    public function CityObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\City', 'City', 'Oid');
    }
}
