<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class BusinessPartnerGroupUser extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstbusinesspartnergroupuser';                
    
public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
public function BusinessPartnerGroupObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartnerGroup', 'BusinessPartnerGroup', 'Oid'); }

    

}