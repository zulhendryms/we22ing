<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class SalesDeliveryDetail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trdsalesdeliverydetail';

    public function AccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'Account', 'Oid'); }
    public function TaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'Tax', 'Oid'); }
    public function SalesDeliveryObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesDelivery', 'SalesDelivery', 'Oid'); }
    public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
    public function ItemUnitObj() { return $this->belongsTo('App\Core\Master\Entities\ItemUnit', 'ItemUnit', 'Oid'); }
    public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
    public function SalesOrderDetailObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesOrderDetail', 'SalesOrderDetail', 'Oid'); }

}