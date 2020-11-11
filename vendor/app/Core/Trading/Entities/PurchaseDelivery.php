<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseDelivery extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trdpurchasedelivery';                
            
public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }
public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
// public function CurrencyRateObj() { return $this->belongsTo('App\Core\Master\Entities\CurrencyRate', 'CurrencyRate', 'Oid'); }
public function AccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'Account', 'Oid'); }
public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
public function WarehouseObj() { return $this->belongsTo('App\Core\Master\Entities\Warehouse', 'Warehouse', 'Oid'); }
public function ProjectObj() { return $this->belongsTo('App\Core\Master\Entities\Project', 'Project', 'Oid'); }
public function TaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'Tax', 'Oid'); }
public function PurchaseOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrder', 'PurchaseOrder', 'Oid'); }

            
public function Details() { return $this->hasMany('App\Core\Trading\Entities\PurchaseDeliveryDetail', 'PurchaseDelivery', 'Oid'); }

}
        