<?php

namespace App\Core\PointOfSale\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class POSETicketReturnDetail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'poseticketreturndetail';                
    
public function POSETicketReturnObj() { return $this->belongsTo('App\Core\PointOfSale\Entities\POSETicketReturn', 'POSETicketReturn', 'Oid'); }
public function TravelTransactionDetailObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelTransactionDetail', 'TravelTransactionDetail', 'Oid'); }
public function POSETicketObj() { return $this->belongsTo('App\Core\POS\Entities\POSETicket', 'POSETicket', 'Oid'); }
public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }

}