<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelGuideGroup extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trvguidegroup';



    public function Contracts()
    {
        return $this->hasMany('App\Core\Travel\Entities\TravelContractGuide', 'TravelGuideGroup', 'Oid');
    }
}
