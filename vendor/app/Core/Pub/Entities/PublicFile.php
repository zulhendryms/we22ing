<?php

namespace App\Core\Pub\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PublicFile extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'pubfile';

    public function PointOfSaleObj() { return $this->belongsTo('App\Core\POS\Entities\PointOfSale', 'PointOfSale', 'Oid'); }
    public function PurchaseRequestObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseRequest', 'PurchaseRequest', 'Oid'); }
    public function PurchaseDeliveryObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseDelivery', 'PurchaseDelivery', 'Oid'); }
    public function PurchaseInvoiceObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseInvoice', 'PurchaseInvoice', 'Oid'); }
    public function CashBankObj() { return $this->belongsTo('App\Core\Accounting\Entities\CashBank', 'CashBank', 'Oid'); }
    public function SalesInvoiceObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesInvoice', 'SalesInvoice', 'Oid'); }
    public function PurchaseOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrder', 'PurchaseOrder', 'Oid'); }
    public function TaskObj() { return $this->belongsTo('App\Core\Collaboration\Entities\Task', 'Task', 'Oid'); }
    public function SalesOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesOrder', 'SalesOrder', 'Oid'); }

}
