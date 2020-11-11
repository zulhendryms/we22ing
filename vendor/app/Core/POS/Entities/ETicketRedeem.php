<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ETicketRedeem extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'poseticketredeem';

}