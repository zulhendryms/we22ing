<?php

namespace App\Core\Accounting\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class CashBankDetail extends BaseModel {

    use BelongsToCompany;

    protected $table = 'acccashbankdetail';

    public function CashBankObj() { return $this->belongsTo('App\Core\Accounting\Entities\CashBank', 'CashBank', 'Oid'); }
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    // public function APInvoiceObj() { return $this->belongsTo('App\Core\Accounting\Entities\AP Invoice', 'APInvoice', 'Oid'); }
    // public function ARInvoiceObj() { return $this->belongsTo('App\Core\Accounting\Entities\AR Invoice', 'ARInvoice', 'Oid'); }
    public function AccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'Account', 'Oid'); }
    // public function TravelPurchaseInvoiceObj() { return $this->belongsTo('App\Core\Travel\Entities\PurchaseInvoice', 'TravelPurchaseInvoice', 'Oid'); }
    // public function TravelSalesTransactionObj() { return $this->belongsTo('App\Core\Travel\Entities\SalesTransaction', 'TravelSalesTransaction', 'Oid'); }
    // public function TravelSalesTransactionDetailObj() { return $this->belongsTo('App\Core\Travel\Entities\SalesTransactionDetail', 'TravelSalesTransactionDetail', 'Oid'); }
    // public function TravelTransactionDetailObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelTransactionDetail', 'TravelTransactionDetail', 'Oid'); }
    public function PurchaseOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrder', 'PurchaseOrder', 'Oid'); }
    public function PurchaseOrderDetailObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseOrderDetail', 'PurchaseOrderDetail', 'Oid'); }
    public function PurchaseInvoiceObj() { return $this->belongsTo('App\Core\Trading\Entities\PurchaseInvoice', 'PurchaseInvoice', 'Oid'); }
    public function SalesOrderObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesOrder', 'SalesOrder', 'Oid'); }
    public function SalesInvoiceObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesInvoice', 'SalesInvoice', 'Oid'); }
    public function CostCenterObj() { return $this->belongsTo('App\Core\Master\Entities\CostCenter', 'CostCenter', 'Oid'); }
    public function SalesOrderDetailObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesOrderDetail', 'SalesOrderDetail', 'Oid'); }

}