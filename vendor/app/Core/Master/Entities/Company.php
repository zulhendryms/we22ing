<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Master\Traits\CompanyFeature;

class Company extends BaseModel 
{
    use CompanyFeature;
    protected $table = 'company';

    public function __get($key)
    {
        switch($key) {
            case "Title": return str_to_upper($this->Name);
        }
        return parent::__get($key);
    }

    public function CompanySourceObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "CompanySource", "Oid"); }
    public function CompanyParentObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "CompanyParent", "Oid"); }
    public function WarehouseObj() { return $this->belongsTo('App\Core\Master\Entities\Warehouse', 'POSDefaultWarehouse', 'Oid'); }
    public function CityObj() { return $this->belongsTo('App\Core\Master\Entities\City', 'City', 'Oid'); }
    public function CountryObj() { return $this->belongsTo('App\Core\Internal\Entities\Country', 'Country', 'Oid'); }
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    public function BusinessPartnerAmountDifferenceObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "BusinessPartnerAmountDifference", "Oid"); }
    public function AccountProfitLossObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "AccountProfitLoss", "Oid"); }
    public function Passengers() { return $this->hasMany("App\Core\Master\Entities\Passenger", "Company", "Oid"); }
    public function CompanyTypeObj() { return $this->belongsTo("App\Core\Master\Entities\CompanyType", "CompanyType", "Oid"); }
    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "CustomerCash", "Oid"); }
    public function PurchaseDiscountAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "PurchaseDiscountAccount", "Oid"); }
    public function SalesDiscountAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "SalesDiscountAccount", "Oid"); }
    public function BusinessPartnerPurchaseDeliveryObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "BusinessPartnerPurchaseDelivery", "Oid"); }
    public function BusinessPartnerPurchaseInvoiceObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "BusinessPartnerPurchaseInvoice", "Oid"); }
    public function BusinessPartnerSalesDeliveryObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "BusinessPartnerSalesDelivery", "Oid"); }
    public function BusinessPartnerSalesInvoiceObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "BusinessPartnerSalesInvoice", "Oid"); }
    public function ItemStockObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "ItemStock", "Oid"); }
    public function ItemAgentObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "ItemAgent", "Oid"); }
    public function ItemSalesIncomeObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "ItemSalesIncome", "Oid"); }
    public function ItemSalesProductionObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "ItemSalesProduction", "Oid"); }
    public function ItemPurchaseExpenseObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "ItemPurchaseExpense", "Oid"); }
    public function ItemPurchaseProductionObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "ItemPurchaseProduction", "Oid"); }
    public function IncomeInProgressObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "IncomeInProgress", "Oid"); }
    public function ExpenseInProgressObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "ExpenseInProgress", "Oid"); }
    public function CashBankExchRateGainObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "CashBankExchRateGain", "Oid"); }
    public function CashBankExchRateLossObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "CashBankExchRateLoss", "Oid"); }
    public function ARAPExchRateGainObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "ARAPExchRateGain", "Oid"); }
    public function ARAPExchRateLossObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "ARAPExchRateLoss", "Oid"); }
    public function AccountIncomeObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "AccountIncome", "Oid"); }
    public function AccountExpenseObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "AccountExpense", "Oid"); }
    public function ItemAccountGroupObj() { return $this->belongsTo("App\Core\Master\Entities\ItemAccountGroup", "ItemAccountGroup", "Oid"); }
    public function BusinessPartnerAccountGroup() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartnerAccountGroup", "BusinessPartnerAccountGroup", "Oid"); }


    public function POSPaymentMethodForChangesObj() { return $this->belongsTo("App\Core\Master\Entities\PaymentMethod", "POSPaymentMethodForChanges", "Oid"); }
    public function POSDefaultWarehouseObj() { return $this->belongsTo("App\Core\Master\Entities\Warehouse", "POSDefaultWarehouse", "Oid"); }
    public function POSDefaultTableObj() { return $this->belongsTo("App\Core\POS\Entities\POSTable", "POSDefaultTable", "Oid"); }
    public function POSDefaultEmployeeObj() { return $this->belongsTo("App\Core\Master\Entities\Employee", "POSDefaultEmployee", "Oid"); }



    /**
     * Set Address value
     * @param string $value
     * @return void
     */
    public function setEthereumETHAddressAttribute($value)
    {
        $this->attributes['EthereumETHAddress'] = encrypt_salted($value);
    }

    /**
     * Get Address value
     * @param string $value
     * @return void
     */
    public function getEthereumETHAddressAttribute($value)
    {
        return decrypt_salted($value);
    }
}