<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class POSETicket extends BaseModel 
{
        use BelongsToCompany;
        protected $table = 'poseticket';                
            
public function PointOfSaleObj() { return $this->belongsTo('App\Core\POS\Entities\POSPointOfSale', 'PointOfSale', 'Oid'); }
public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
public function ETicketUploadObj() { return $this->belongsTo('App\Core\PointOfSale\Entities\POSETicketUpload', 'ETicketUpload', 'Oid'); }
public function RedeemAddressObj() { return $this->belongsTo('App\Core\PointOfSale\Entities\PosTicketRedeem', 'RedeemAddress', 'Oid'); }
public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }
public function TravelTransactionDetailObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelTransactionDetail', 'TravelTransactionDetail', 'Oid'); }
public function PurchaseEticketObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseTicket', 'PurchaseEticket', 'Oid'); }
public function PurchaseInvoiceObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseInvoice', 'PurchaseInvoice', 'Oid'); }

            

        }
        