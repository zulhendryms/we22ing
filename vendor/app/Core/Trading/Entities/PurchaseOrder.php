<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseOrder extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trdpurchaseorder';

    public function PublicPostObj() { return $this->belongsTo('App\Core\Pub\Entities\PublicPost', 'ObjectOid', 'Oid'); }
    public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }
    public function AccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'Account', 'Oid'); }
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
    public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
    public function WarehouseObj() { return $this->belongsTo('App\Core\Master\Entities\Warehouse', 'Warehouse', 'Oid'); }
    public function ProjectObj() { return $this->belongsTo('App\Core\Master\Entities\Project', 'Project', 'Oid'); }
    public function PaymentTermObj() { return $this->belongsTo('App\Core\Master\Entities\PaymentTerm', 'PaymentTerm', 'Oid'); }
    public function TaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'Tax', 'Oid'); }
    public function PurchaseRequestObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseRequest', 'PurchaseRequest', 'Oid'); }
    public function RequestorObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'Requestor', 'Oid'); }
    public function Requestor1Obj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Requestor1', 'Oid'); }
    public function Requestor2Obj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Requestor2', 'Oid'); }
    public function Requestor3Obj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Requestor3', 'Oid'); }
    public function TruckingPrimeMoverObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingPrimeMover', 'TruckingPrimeMover', 'Oid'); }
    public function Supplier1Obj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'Supplier1', 'Oid'); }
    public function Supplier2Obj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'Supplier2', 'Oid'); }
    public function Supplier3Obj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'Supplier3', 'Oid'); }
    public function Supplier1PaymentTermObj() { return $this->belongsTo('App\Core\Master\Entities\PaymentTerm', 'Supplier1PaymentTerm', 'Oid'); }
    public function Supplier2PaymentTermObj() { return $this->belongsTo('App\Core\Master\Entities\PaymentTerm', 'Supplier2PaymentTerm', 'Oid'); }
    public function Supplier3PaymentTermObj() { return $this->belongsTo('App\Core\Master\Entities\PaymentTerm', 'Supplier3PaymentTerm', 'Oid'); }
    public function Approval1Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'Approval1', 'Oid'); }
    public function Approval2Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'Approval2', 'Oid'); }
    public function Approval3Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'Approval3', 'Oid'); }
    public function DepartmentObj() { return $this->belongsTo('App\Core\Master\Entities\Department', 'Department', 'Oid'); }
    public function PurchaserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'Purchaser', 'Oid'); }
    public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }

    public function Logs() { return $this->hasMany('App\Core\Trading\Entities\PurchaseOrderLog', 'PurchaseOrder', 'Oid'); }
    public function Details(){return $this->hasMany('App\Core\Trading\Entities\PurchaseOrderDetail', 'PurchaseOrder', 'Oid');}
    public function Comments() { return $this->hasMany('App\Core\Pub\Entities\PublicComment', 'PublicPost', 'Oid'); }
    public function Images() { return $this->hasMany('App\Core\Master\Entities\Image', 'PublicPost', 'Oid'); }
    public function Files() { return $this->hasMany('App\Core\Pub\Entities\PublicFile', 'PublicPost', 'Oid'); }
    public function Approvals() { return $this->hasMany('App\Core\Pub\Entities\PublicApproval', 'PublicPost', 'Oid'); }
}
