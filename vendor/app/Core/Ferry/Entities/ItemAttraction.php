<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Base\Traits\Activable;

class ItemAttraction extends BaseModel {
    protected $table = 'feritemattraction';
    protected $gcrecord = false;
}