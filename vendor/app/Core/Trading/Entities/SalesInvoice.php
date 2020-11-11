<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class SalesInvoice extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trdsalesinvoice';
        
    public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }
    public function AccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'Account', 'Oid'); }
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    public function ProjectObj() { return $this->belongsTo('App\Core\Master\Entities\Project', 'Project', 'Oid'); }
    public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
    public function WarehouseObj() { return $this->belongsTo('App\Core\Master\Entities\Warehouse', 'Warehouse', 'Oid'); }
    public function PaymentTermObj() { return $this->belongsTo('App\Core\Master\Entities\PaymentTerm', 'PaymentTerm', 'Oid'); }
    public function CurrencyRateObj() { return $this->belongsTo('App\Core\Master\Entities\CurrencyRate', 'CurrencyRate', 'Oid'); }
    public function DiscountAccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'DiscountAccount', 'Oid'); }
    public function AdditionalAccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'AdditionalAccount', 'Oid'); }
    public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
    public function TaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'Tax', 'Oid'); }
    public function TravelCommissionObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelCommission', 'TravelCommission', 'Oid'); }
    public function CashBankPrepaidObj() { return $this->belongsTo('App\Core\Accounting\Entities\CashBank', 'CashBankPrepaid', 'Oid'); }
    public function AccountPrepaidObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'AccountPrepaid', 'Oid'); }
    public function PointOfSaleObj() { return $this->belongsTo('App\Core\POS\Entities\PointOfSale', 'PointOfSale', 'Oid'); }
    public function SalesOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesOrder', 'SalesOrder', 'Oid'); }
    public function AccountCashBankObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'AccountCashBank', 'Oid'); }

    public function Stocks() { return $this->hasMany("App\Core\Trading\Entities\TransactionStock", "SalesInvoice", "Oid"); }
    public function Details() { return $this->hasMany('App\Core\Trading\Entities\SalesInvoiceDetail', 'SalesInvoice', 'Oid'); }
    public function DetailTravels() { return $this->hasMany('App\Core\Trading\Entities\SalesInvoiceDetailTravel', 'SalesInvoice', 'Oid'); }
    public function Journals() { return $this->hasMany('App\Core\Accounting\Entities\Journal', 'SalesInvoice', 'Oid'); }
}
