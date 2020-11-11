<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceInsuranceGroup extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsinsurancegroup';                
            

            
    public function Details() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceInsuranceGroupDetail', 'HumanResourceInsuranceGroup', 'Oid'); }

}