<?php

namespace App\Core\PointOfSale\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class POSSession extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'possession';                
    
public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
public function WarehouseObj() { return $this->belongsTo('App\Core\Master\Entities\Warehouse', 'Warehouse', 'Oid'); }
public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }

    

}