<?php

namespace App\Core\Accounting\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class APInvoice extends BaseModel {
    use BelongsToCompany;
    protected $table = 'accapinvoice';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Code.' '.$this->Date;
            case "FullTitle": return $this->Code.' '.$this->Date.' '.$this->BusinessPartnerObj->Name;
        }
        return parent::__get($key);
    }

    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function AccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "Account", "Oid"); }
    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function StatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid"); }
    public function Details()  { return $this->hasMany("App\Core\Travel\Entities\TravelTransactionDetail", "APInvoice", "Oid"); }
    public function Journals() { return $this->hasMany("App\Core\Accounting\Entities\Journal", "APInvoice", "Oid"); }
}