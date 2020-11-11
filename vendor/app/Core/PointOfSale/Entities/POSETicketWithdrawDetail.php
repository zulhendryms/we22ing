<?php

namespace App\Core\PointOfSale\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class POSETicketWithdrawDetail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'poseticketwithdrawdetail';                
    
public function POSETicketWithdrawObj() { return $this->belongsTo('App\Core\PointOfSale\Entities\POSETicketWithdraw', 'POSETicketWithdraw', 'Oid'); }
public function POSETicketObj() { return $this->belongsTo('App\Core\POS\Entities\POSETicket', 'POSETicket', 'Oid'); }

            

}
        