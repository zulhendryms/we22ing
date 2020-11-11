<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class POSETicketLog extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'poseticketlog';                
    
public function PurchaseInvoiceObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseInvoice', 'PurchaseInvoice', 'Oid'); }
public function POSETicketObj() { return $this->belongsTo('App\Core\PointOfSale\Entities\POSTicket', 'POSETicket', 'Oid'); }
public function PointOfSaleObj() { return $this->belongsTo('App\Core\POS\Entities\PointOfSale', 'PointOfSale', 'Oid'); }

    

}