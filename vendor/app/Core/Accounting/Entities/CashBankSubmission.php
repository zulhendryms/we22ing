<?php

namespace App\Core\Accounting\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class CashBankSubmission extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'acccashbanksubmission';
    
    public function CompanyObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Company', 'Company', 'Oid');
    }
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    public function DepartmentObj() { return $this->belongsTo('App\Core\Master\Entities\Department', 'Department', 'Oid'); }
    public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
    public function RequestorObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'Requestor', 'Oid'); }


    public function Details() { return $this->hasMany('App\Core\Accounting\Entities\CashBankSubmissionDetail', 'CashBankSubmission', 'Oid'); }
    public function Journals() { return $this->hasMany("App\Core\Accounting\Entities\Journal", "CashBankSubmission", "Oid"); }
    public function Comments() { return $this->hasMany('App\Core\Pub\Entities\PublicComment', 'PublicPost', 'Oid'); }
    public function Approvals() { return $this->hasMany('App\Core\Pub\Entities\PublicApproval', 'ObjectOid', 'Oid'); }

}
