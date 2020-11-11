<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class BusinessPartnerAccountGroup extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstbusinesspartneraccountgroup';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function PurchaseCurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "PurchaseCurrency", "Oid"); }
    public function PurchaseDeliveryObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "PurchaseDelivery", "Oid"); }
    public function PurchaseInvoiceObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "PurchaseInvoice", "Oid"); }
    public function PurchaseTaxObj() { return $this->belongsTo("App\Core\Master\Entities\Tax", "PurchaseTax", "Oid"); }
    public function PurchaseTermObj() { return $this->belongsTo("App\Core\Master\Entities\PaymentTerm", "PurchaseTerm", "Oid"); }
    public function SalesCurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "SalesCurrency", "Oid"); }
    public function SalesDeliveryObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "SalesDelivery", "Oid"); }
    public function SalesInvoiceObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "SalesInvoice", "Oid"); }
    public function SalesTaxObj() { return $this->belongsTo("App\Core\Master\Entities\Tax", "SalesTax", "Oid"); }
    public function SalesTermObj() { return $this->belongsTo("App\Core\Master\Entities\PaymentTerm", "SalesTerm", "Oid"); }
}