<?php

namespace App\Core\Pub\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PublicPost extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'pubpost';


    public function CreatedByObj(){return $this->belongsTo('App\Core\Security\Entities\User', 'CreatedBy', 'Oid');}
    public function AccountObj(){return $this->belongsTo("App\Core\Accounting\Entities\Account", "Account", "Oid");}
    public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid');}
    public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid');}
    public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid');}
    public function PurchaseOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrder', 'PurchaseOrder', 'Oid'); }
    public function DepartmentObj() { return $this->belongsTo('App\Core\Master\Entities\Department', 'Department', 'Oid'); }
    public function CashBankObj() { return $this->belongsTo('App\Core\Accounting\Entities\CashBank', 'CashBank', 'Oid'); }
    public function LastCommentedByObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'LastCommentedBy', 'Oid'); }
    public function SalesOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesOrder', 'SalesOrder', 'Oid'); }
    public function CashBankSubmissionObj() { return $this->belongsTo('App\Core\Accounting\Entities\CashBankSubmission', 'CashBankSubmission', 'Oid'); }

    public function Notifications(){return $this->hasMany('App\Core\Security\Entities\Notification', 'PublicPost', 'Oid');}
    public function Comments(){return $this->hasMany('App\Core\Pub\Entities\PublicComment', 'PublicPost', 'Oid');}
    public function Approvals(){return $this->hasMany('App\Core\Pub\Entities\PublicApproval', 'PublicPost', 'Oid');}
    public function Likes() { return $this->hasMany('App\Core\Pub\Entities\PublicPostLike', 'PublicPost', 'Oid'); }
}
