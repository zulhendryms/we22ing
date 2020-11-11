<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseOrderDetail extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trdpurchaseorderdetail';

    public function PurchaseOrderObj(){return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrder', 'PurchaseOrder', 'Oid');}
    public function AccountObj(){return $this->belongsTo('App\Core\Accounting\Entities\Account', 'Account', 'Oid');}
    public function TaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'Tax', 'Oid'); }
    public function PurchaseInvoiceObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseInvoice', 'PurchaseInvoice', 'Oid'); }
    public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
    public function ItemUnitObj() { return $this->belongsTo('App\Core\Master\Entities\ItemUnit', 'ItemUnit', 'Oid'); }
    public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
    public function PurchaseDeliveryDetailObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseDeliveryDetail', 'PurchaseDeliveryDetail', 'Oid'); }
    public function CostCenterObj() { return $this->belongsTo('App\Core\Master\Entities\CostCenter', 'CostCenter', 'Oid'); }
    public function PurchaseRequestDetailObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseRequestDetail', 'PurchaseRequestDetail', 'Oid'); }
}
