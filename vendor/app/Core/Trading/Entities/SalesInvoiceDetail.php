<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class SalesInvoiceDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trdsalesinvoicedetail';


    public function ItemUnitObj() { return $this->belongsTo('App\Core\Master\Entities\ItemUnit', 'ItemUnit', 'Oid'); }
    public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
    public function SalesInvoiceObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesInvoice', 'SalesInvoice', 'Oid'); }
    public function AccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'Account', 'Oid'); }
    public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
    public function TaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'Tax', 'Oid'); }
    public function SalesOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesOrder', 'SalesOrder', 'Oid'); }
    public function SalesOrderDetailObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesOrderDetail', 'SalesOrderDetail', 'Oid'); }
}