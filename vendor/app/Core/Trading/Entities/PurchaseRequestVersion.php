<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseRequestVersion extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trdpurchaserequestversion';                
    
public function Supplier1Obj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'Supplier1', 'Oid'); }
public function Supplier2Obj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'Supplier2', 'Oid'); }
public function Supplier3Obj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'Supplier3', 'Oid'); }
public function PurchaserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'Purchaser', 'Oid'); }
public function TruckingPrimeMoverObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingPrimeMover', 'TruckingPrimeMover', 'Oid'); }
public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
public function Requestor1Obj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Requestor1', 'Oid'); }
public function Requestor2Obj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Requestor2', 'Oid'); }
public function Requestor3Obj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Requestor3', 'Oid'); }
public function CostCenterObj() { return $this->belongsTo('App\Core\Master\Entities\CostCenter', 'CostCenter', 'Oid'); }
public function DepartmentObj() { return $this->belongsTo('App\Core\Master\Entities\Department', 'Department', 'Oid'); }
public function Approval1Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'Approval1', 'Oid'); }
public function Approval2Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'Approval2', 'Oid'); }
public function Approval3Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'Approval3', 'Oid'); }

    
public function Details() { return $this->hasMany('App\Core\Trading\Entities\PurchaseRequestDetailVersion', 'PurchaseRequestVersion', 'Oid'); }

}