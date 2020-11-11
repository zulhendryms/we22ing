<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class FerryItem extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'feritem';                
    

    

}
