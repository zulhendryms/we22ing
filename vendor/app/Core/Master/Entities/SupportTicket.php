<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class SupportTicket extends BaseModel {
    use BelongsToCompany;
    protected $table = 'mstsupportticket';

}