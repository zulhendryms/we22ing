<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionOrder extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdorder';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function StatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid"); }
    public function CustomerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "Customer", "Oid"); }
    public function Details() { return $this->hasMany("App\Core\Production\Entities\ProductionOrderDetail", "ProductionOrder","Oid"); }
    public function Items() { return $this->hasMany("App\Core\Production\Entities\ProductionOrderItem", "ProductionOrder","Oid"); }
    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function DepartmentObj() { return $this->belongsTo("App\Core\Master\Entities\Department", "Department", "Oid"); }
}