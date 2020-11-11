<?php

    namespace App\Core\Master\Entities;

    use App\Core\Base\Entities\BaseModel;
    use App\Core\Base\Traits\BelongsToCompany;
    
    class ItemAccountGroup extends BaseModel 
    {
        use BelongsToCompany;
        protected $table = 'mstitemaccountgroup';                
        
        public function ItemTypeObj() { return $this->belongsTo('App\Core\Internal\Entities\ItemType', 'ItemType', 'Oid'); }
        public function ItemMethodObj() { return $this->belongsTo('App\Core\Internal\Entities\ItemMethod', 'ItemMethod', 'Oid'); }
        public function StockAccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'StockAccount', 'Oid'); }
        public function PurchaseCurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'PurchaseCurrency', 'Oid'); }
        public function PurchaseProductionObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'PurchaseProduction', 'Oid'); }
        public function PurchaseExpenseObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'PurchaseExpense', 'Oid'); }
        public function SalesProductionObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'SalesProduction', 'Oid'); }
        public function SalesIncomeObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'SalesIncome', 'Oid'); }
        public function SalesCurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'SalesCurrency', 'Oid'); }
        public function AgentAccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'AgentAccount', 'Oid'); }
        public function AgentCurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'AgentCurrency', 'Oid'); }
        public function PurchaseDeliveryObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'PurchaseDelivery', 'Oid'); }
        public function PurchaseInvoiceObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'PurchaseInvoice', 'Oid'); }
        public function PurchaseTaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'PurchaseTax', 'Oid'); }
        public function SalesDeliveryObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'SalesDelivery', 'Oid'); }
        public function SalesInvoiceObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'SalesInvoice', 'Oid'); }
        public function SalesTaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'SalesTax', 'Oid'); }

        

    }
    