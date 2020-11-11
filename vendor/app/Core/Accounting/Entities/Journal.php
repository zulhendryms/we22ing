<?php

namespace App\Core\Accounting\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Journal extends BaseModel {

    use BelongsToCompany;    
    protected $table = 'accjournal';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Code.' '.$this->Date;
            case "FullTitle": return $this->Code.' '.$this->Date.' '.$this->BusinessPartnerObj->Name;
        }
        return parent::__get($key);
    }

    public function AccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "Account", "Oid"); }
    public function JournalTypeObj() { return $this->belongsTo("App\Core\Internal\Entities\JournalType", "JournalType", "Oid"); }
    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function StatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid"); }
    public function PointOfSaleObj()  { return $this->belongsTo("App\Core\POS\Entities\PointOfSale", "PointOfSale", "Oid"); }
    public function CashBankObj()  { return $this->belongsTo("App\Core\Accounting\Entities\CashBank", "CashBank", "Oid"); }
}