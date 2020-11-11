<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TransactionStock extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trdtransactionstock';

    public function JournalTypeObj() { return $this->belongsTo('App\Core\Internal\Entities\JournalType', 'JournalType', 'Oid'); }
    // public function JournalTypeObj() { return $this->belongsTo("App\Core\System\Entities\JournalType", "JournalType", "Oid"); }
    public function JournalObj() { return $this->belongsTo("App\Core\Accounting\Entities\Journal", "Journal", "Oid"); }
    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function PurchaseDeliveryObj()  { return $this->belongsTo("App\Core\Trading\Entities\PurchaseDelivery", "PurchaseDelivery", "Oid"); }
    public function PurchaseInvoiceObj()  { return $this->belongsTo("App\Core\Trading\Entities\PurchaseInvoice", "PurchaseInvoice", "Oid"); }
    public function SalesDeliveryObj()  { return $this->belongsTo("App\Core\Trading\Entities\SalesDelivery", "SalesDelivery", "Oid"); }
    public function SalesInvoiceObj()  { return $this->belongsTo("App\Core\Trading\Entities\SalesInvoice", "SalesInvoice", "Oid"); }
    public function PointOfSaleObj()  { return $this->belongsTo("App\Core\POS\Entities\PointOfSale", "PointOfSale", "Oid"); }
    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid"); }
    public function WarehouseObj() { return $this->belongsTo("App\Core\Master\Entities\Warehouse", "Warehouse", "Oid"); }
    public function ProjectObj() { return $this->belongsTo("App\Core\Master\Entities\Project", "Project", "Oid"); }
    public function StatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid"); }

    public function StockTransferObj() { return $this->belongsTo('App\Core\Trading\Entities\StockTransfer', 'StockTransfer', 'Oid'); }
    public function POSETicketUploadObj() { return $this->belongsTo('App\Core\PointOfSale\Entities\POSETicketUpload', 'POSETicketUpload', 'Oid'); }
    public function POSSessionObj() { return $this->belongsTo('App\Core\PointOfSale\Entities\POSSession', 'POSSession', 'Oid'); }
}