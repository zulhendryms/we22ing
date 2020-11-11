<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class SalesOrder extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trdsalesorder';

    public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
    public function CurrencyRateObj() { return $this->belongsTo('App\Core\Master\Entities\CurrencyRate', 'CurrencyRate', 'Oid'); }
    public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }
    public function AccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'Account', 'Oid'); }
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
    public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
    public function WarehouseObj() { return $this->belongsTo('App\Core\Master\Entities\Warehouse', 'Warehouse', 'Oid'); }
    public function ProjectObj() { return $this->belongsTo('App\Core\Master\Entities\Project', 'Project', 'Oid'); }
    public function PaymentTermObj() { return $this->belongsTo('App\Core\Master\Entities\PaymentTerm', 'PaymentTerm', 'Oid'); }
    public function TaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'Tax', 'Oid'); }
    public function DepartmentObj() { return $this->belongsTo('App\Core\Master\Entities\Department', 'Department', 'Oid'); }


    public function Details() { return $this->hasMany('App\Core\Trading\Entities\SalesOrderDetail', 'SalesOrder', 'Oid'); }
    public function Comments() { return $this->hasMany('App\Core\Pub\Entities\PublicComment', 'PublicPost', 'Oid'); }
    public function Images() { return $this->hasMany('App\Core\Master\Entities\Image', 'PublicPost', 'Oid'); }
    public function Files() { return $this->hasMany('App\Core\Pub\Entities\PublicFile', 'PublicPost', 'Oid'); }
    public function Approvals() { return $this->hasMany('App\Core\Pub\Entities\PublicApproval', 'PublicPost', 'Oid'); }
}
