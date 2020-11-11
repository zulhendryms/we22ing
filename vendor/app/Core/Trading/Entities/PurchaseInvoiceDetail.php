<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseInvoiceDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trdpurchaseinvoicedetail';

    public function TaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'Tax', 'Oid'); }
    public function PurchaseInvoiceObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseInvoice', 'PurchaseInvoice', 'Oid'); }
    public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
    public function ItemUnitObj() { return $this->belongsTo('App\Core\Master\Entities\ItemUnit', 'ItemUnit', 'Oid'); }
    public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
    public function PurchaseDeliveryDetailObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseDeliveryDetail', 'PurchaseDeliveryDetail', 'Oid'); }
    public function PurchaseOrderDetailObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrderDetail', 'PurchaseOrderDetail', 'Oid'); }
}