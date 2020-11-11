<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseInvoice extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trdpurchaseinvoice';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Code.' '.$this->Date;
            case "FullTitle": return $this->Code.' '.$this->Date.' '.$this->BusinessPartnerObj->Name;
        }
        return parent::__get($key);
    }

    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid"); }
    public function AccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "Account", "Oid"); }
    public function WarehouseObj() { return $this->belongsTo("App\Core\Master\Entities\Warehouse", "Warehouse", "Oid"); }
    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function StatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid"); }
    public function Details()  { return $this->hasMany("App\Core\Trading\Entities\PurchaseInvoiceDetail", "PurchaseInvoice", "Oid"); }
    public function Journals() { return $this->hasMany("App\Core\Accounting\Entities\Journal", "PurchaseInvoice", "Oid"); }
    public function Stocks() { return $this->hasMany("App\Core\Trading\Entities\TransactionStock", "PurchaseInvoice", "Oid"); }
    public function AdditionalAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "AdditionalAccount", "Oid"); }
    public function DiscountAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "DiscountAccount", "Oid"); }
    public function ETickets(){ return $this->hasMany("App\Core\POS\Entities\ETicket", "PurchaseInvoice", "Oid");}
    public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
    public function CurrencyRateObj() { return $this->belongsTo('App\Core\Master\Entities\CurrencyRate', 'CurrencyRate', 'Oid'); }
    public function PaymentTermObj() { return $this->belongsTo('App\Core\Master\Entities\PaymentTerm', 'PaymentTerm', 'Oid'); }
    public function ProjectObj() { return $this->belongsTo('App\Core\Master\Entities\Project', 'Project', 'Oid'); }
    public function TaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'Tax', 'Oid'); }
    public function CashBankPrepaidObj() { return $this->belongsTo('App\Core\Accounting\Entities\CashBank', 'CashBankPrepaid', 'Oid'); }
    public function AccountPrepaidObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'AccountPrepaid', 'Oid'); }
    public function PurchaseOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrder', 'PurchaseOrder', 'Oid'); }   
   
    public function TravelTransactionObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelTransaction', 'TravelTransaction', 'Oid'); }
    public function PurchaseDeliveryObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseDelivery', 'PurchaseDelivery', 'Oid'); }
}