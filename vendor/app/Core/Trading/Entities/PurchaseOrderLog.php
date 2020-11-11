<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseOrderLog extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trdpurchaseorderlog';                
    
public function PurchaseOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrder', 'PurchaseOrder', 'Oid'); }
public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
public function NextUserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'NextUser', 'Oid'); }
}