<?php

namespace App\Core\Trucking\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TruckingTrailer extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trctrailer';
}
