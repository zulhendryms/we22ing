<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class FerryCountry extends BaseModel {
    use BelongsToCompany;
    protected $table = 'fercountry';
    protected $gcrecord = false;
}