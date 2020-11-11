<?php

namespace App\Core\Pub\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PublicApproval extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'pubapproval';

    public function NextUserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'NextUser', 'Oid'); }
    public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
    public function PurchaseOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrder', 'PurchaseOrder', 'Oid'); }
    public function CashBankObj() { return $this->belongsTo('App\Core\Accounting\Entities\CashBank', 'CashBank', 'Oid'); }
    public function DepartmentObj() { return $this->belongsTo('App\Core\Master\Entities\Department', 'Department', 'Oid'); }
    public function PublicPostObj() { return $this->belongsTo('App\Core\Pub\Entities\PublicPost', 'PublicPost', 'Oid'); }
    public function SalesOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesOrder', 'SalesOrder', 'Oid'); }
}
