<?php

namespace App\Core\HumanResource\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class HumanResourceInsuranceGroupDetail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'hrsinsurancegroupdetail';                
            
    public function HumanResourceInsuranceGroupObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceInsuranceGroup', 'HumanResourceInsuranceGroup', 'Oid'); }

}