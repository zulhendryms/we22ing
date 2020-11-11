<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\POS\Traits\HasBalance;


/**
 * @property-read boolean $IsMajestic
 */
class BusinessPartner extends BaseModel {
    // use BelongsToCompany, HasBalance;
    use HasBalance;
    protected $table = 'mstbusinesspartner';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
            case "IsMajestic": return $this->APICode == "api_3";
            case "IsSeawheel": return $this->APICode == "api_1";
        }
        return parent::__get($key);
    }

    public function scopeCash() { return $this->where('Code', 'CASH'); }
    public function CompanyObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "Company", "Oid"); }
    public function CityObj() { return $this->belongsTo("App\Core\Master\Entities\City", "City", "Oid"); }
    public function BusinessPartnerGroupObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartnerGroup", "BusinessPartnerGroup", "Oid"); }
    public function BusinessPartnerAccountGroupObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartnerAccountGroup", "BusinessPartnerAccountGroup", "Oid"); }    
    public function Ports() { return $this->hasMany("App\Core\Ferry\Entities\BusinessPartnerPort", "BusinessPartner", "Oid"); }
    public function Addresses() { return $this->hasMany("App\Core\Master\Entities\BusinessPartnerAddress", "BusinessPartner", "Oid"); }  
    public function Contacts() { return $this->hasMany("App\Core\Master\Entities\BusinessPartnerContact", "BusinessPartner", "Oid"); }
    public function TransportDrivers() { return $this->hasMany("App\Core\Travel\Entities\TravelTransportDriver", "BusinessPartner", "Oid"); }
    public function AgentCurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "AgentCurrency", "Oid"); }
    public function PurchaseCurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "PurchaseCurrency", "Oid"); }
    public function SalesCurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "SalesCurrency", "Oid"); }
    public function CompanyCreditCardObj() { return $this->belongsTo("App\Core\Master\Entities\CompanyCreditCard", "CompanyCreditCard", "Oid"); }
    public function PurchaseTaxObj() { return $this->belongsTo("App\Core\Master\Entities\Tax", "PurchaseTax", "Oid"); }
    public function PurchaseTermObj() { return $this->belongsTo("App\Core\Master\Entities\PaymentTerm", "PurchaseTerm", "Oid"); }
    public function SalesTermObj() { return $this->belongsTo("App\Core\Master\Entities\PaymentTerm", "SalesTerm", "Oid"); }
    public function SalesTaxObj() { return $this->belongsTo("App\Core\Master\Entities\Tax", "SalesTax", "Oid"); }
    public function SalesEmployeeObj() { return $this->belongsTo("App\Core\Master\Entities\Employee", "SalesEmployee", "Oid"); }
    public function SalesOperationObj() { return $this->belongsTo("App\Core\Master\Entities\Employee", "SalesOperation", "Oid"); }
}