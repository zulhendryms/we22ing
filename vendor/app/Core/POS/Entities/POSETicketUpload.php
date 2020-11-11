<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class POSETicketUpload extends BaseModel {
    use BelongsToCompany;
    protected $table = 'poseticketupload';

    public function ETickets()
    {
        return $this->hasMany("App\Core\POS\Entities\ETicket", "ETicketUpload", "Oid");
    }

    public function ItemParentObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "ItemParent", "Oid"); }
    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid"); }
    public function WarehouseObj() { return $this->belongsTo("App\Core\Master\Entities\Warehouse", "Warehouse", "Oid"); }
    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function AccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "Account", "Oid"); }
    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function StatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid"); }
    public function Journals() { return $this->hasMany("App\Core\Accounting\Entities\Journal", "POSETicketUpload", "Oid"); }
    public function Stocks() { return $this->hasMany("App\Core\Trading\Entities\TransactionStock", "POSETicketUpload", "Oid"); }
    public function ItemContentObj() { return $this->belongsTo("App\Core\Master\Entities\ItemContent", "ItemContent", "Oid"); }
    
}