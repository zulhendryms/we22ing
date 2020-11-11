<?php

namespace App\Core\Pub\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PublicComment extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'pubcomment';

    public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
    public function PurchaseRequestObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseRequest', 'PurchaseRequest', 'Oid'); }
    public function PurchaseOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrder', 'PurchaseOrder', 'Oid'); }
    public function CashBankObj() { return $this->belongsTo('App\Core\Accounting\Entities\CashBank', 'CashBank', 'Oid'); }
    public function PublicPostObj() { return $this->belongsTo('App\Core\Pub\Entities\PublicPost', 'PublicPost', 'Oid'); }
    public function TaskObj() { return $this->belongsTo('App\Core\Collaboration\Entities\Task', 'Task', 'Oid'); }
    public function SalesOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesOrder', 'SalesOrder', 'Oid'); }
    public function TruckingTransactionFuelObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingTransactionFuel', 'TruckingTransactionFuel', 'Oid'); }
    public function CashBankSubmissionObj() { return $this->belongsTo('App\Core\Accounting\Entities\CashBankSubmission', 'CashBankSubmission', 'Oid'); }


}
