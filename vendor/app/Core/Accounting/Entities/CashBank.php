<?php

namespace App\Core\Accounting\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class CashBank extends BaseModel {

    use BelongsToCompany;

    protected $table = 'acccashbank';

    public function __get($property) 
    {
        switch($property) {
            case "Title": return $this->Code.' '.$this->Date;
            case "FullTitle": return $this->Code.' '.$this->Date;
            case 'IsTransfer': return $this->Type == 4;
            case 'IsInvoice':  return $this->Type == 2 || $this->Type == 3;
            case 'IsPayment': return $this->Type == 3;
            case 'IsReceipt': return $this->Type == 2;
            case 'IsIncome': return $this->Type == 0;
            case 'IsExpense': return $this->Type == 1;
            case 'TypeName': return $this->getType();
        }
        return parent::__get($property);
    }

    public function getType()
    {
        switch($this->Type) {
            case 0: return 'Income';
            case 1: return 'Expense';
            case 2: return 'Receipt';
            case 3: return 'Payment';
            case 4: return 'Transfer';
        }
    }
    
    public function PublicPostObj() { return $this->belongsTo('App\Core\Pub\Entities\PublicPost', 'ObjectOid', 'Oid'); }
    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function AccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "Account", "Oid"); }
    public function TransferAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "TransferAccount", "Oid"); }
    public function AdditionalAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "AdditionalAccount", "Oid"); }
    public function DiscountAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "DiscountAccount", "Oid"); }
    public function StatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid"); }
    public function PrepaidAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "PrepaidAccount", "Oid"); }
    public function CurrencyRateObj() { return $this->belongsTo('App\Core\Master\Entities\CurrencyRate', 'CurrencyRate', 'Oid'); }
    public function WarehouseObj() { return $this->belongsTo('App\Core\Master\Entities\Warehouse', 'Warehouse', 'Oid'); }
    public function ParentObj() { return $this->belongsTo('App\Core\Accounting\Entities\CashBank', 'Parent', 'Oid'); }
    public function TransferCurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'TransferCurrency', 'Oid'); }
    public function TravelTransactionObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelTransaction', 'TravelTransaction', 'Oid'); }
    public function ProjectObj() { return $this->belongsTo('App\Core\Master\Entities\Project', 'Project', 'Oid'); }
    public function ReconcileUserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'ReconcileUser', 'Oid'); }
    public function Requestor1Obj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Requestor1', 'Oid'); }
    public function Requestor2Obj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Requestor2', 'Oid'); }
    public function DepartmentObj() { return $this->belongsTo('App\Core\Master\Entities\Department', 'Department', 'Oid'); }

    public function Details() { return $this->hasMany("App\Core\Accounting\Entities\CashBankDetail", "CashBank", "Oid"); }
    public function Journals() { return $this->hasMany("App\Core\Accounting\Entities\Journal", "CashBank", "Oid"); }
    public function Emails() { return $this->hasMany("App\Core\Master\Entities\Email", "CashBank", "Oid"); }
    public function Comments() { return $this->hasMany('App\Core\Pub\Entities\PublicComment', 'PublicPost', 'Oid'); }
    public function Images() { return $this->hasMany('App\Core\Master\Entities\Image', 'PublicPost', 'Oid'); }
    public function Files() { return $this->hasMany('App\Core\Pub\Entities\PublicFile', 'PublicPost', 'Oid'); }
    public function Approvals() { return $this->hasMany('App\Core\Pub\Entities\PublicApproval', 'PublicPost', 'Oid'); }
}