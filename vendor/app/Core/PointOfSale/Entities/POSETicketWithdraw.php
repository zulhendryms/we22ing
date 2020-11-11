<?php

namespace App\Core\PointOfSale\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class POSETicketWithdraw extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'poseticketwithdraw';                
            
public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
public function ItemContentObj() { return $this->belongsTo('App\Core\Master\Entities\ItemContent', 'ItemContent', 'Oid'); }
public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }

            
public function Details() { return $this->hasMany('App\Core\PointOfSale\Entities\POSETicketWithdrawDetail', 'POSETicketWithdraw', 'Oid'); }

}
        