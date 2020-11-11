<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class BankUser extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstbankuser';                
    
public function BankObj() { return $this->belongsTo('App\Core\Master\Entities\Bank', 'Bank', 'Oid'); }
public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
}
