<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Faq extends BaseModel {
    use BelongsToCompany;
    protected $table = 'mstfaqcontent';
}