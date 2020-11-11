<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseEticket extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trdpurchaseeticket';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Code.' '.$this->Date;
            case "FullTitle": return $this->Code.' '.$this->Date.' '.$this->BusinessPartnerObj->Name;
        }
        return parent::__get($key);
    }

    public function ItemParentObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "ItemParent", "Oid"); }
    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid"); }
    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function AccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "Account", "Oid"); }
    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function StatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid"); }
    public function ETickets(){ return $this->hasMany("App\Core\POS\Entities\ETicket", "PurchaseEticket", "Oid");}
    public function Journals() { return $this->hasMany("App\Core\Accounting\Entities\Journal", "PurchaseEticket", "Oid"); }
    public function Stocks() { return $this->hasMany("App\Core\Trading\Entities\TransactionStock", "PurchaseEticket", "Oid"); }
    public function AdditionalAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "AdditionalAccount", "Oid"); }
    public function DiscountAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "DiscountAccount", "Oid"); }
}