<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class CostCenterLog extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstcostcenterlog';                
    
public function CostCenterObj() { return $this->belongsTo('App\Core\Master\Entities\CostCenter', 'CostCenter', 'Oid'); }
public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }

}