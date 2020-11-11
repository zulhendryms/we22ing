<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PointOfSale extends BaseModel {
    
    use BelongsToCompany;
    use \App\Core\POS\Traits\HasETickets;
    use \App\Core\Travel\Traits\HasTravelTransaction;
    use \App\Core\Deal\Traits\HasDealTransaction;
    use \App\Core\Ferry\Traits\HasFerryTransaction;

    protected $table = 'pospointofsale';
    const XP_TARGET_TYPE = 'Cloud_ERP.Module.BusinessObjects.POS.PointOfSale';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Code.' '.$this->Date;
            case "FullTitle": return $this->Code.' '.$this->Date.' '.$this->BusinessPartnerObj->Name;
        }
        return parent::__get($key);
    }

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if (!isset($model->Code)) $model->Code = now()->format('ymdHis').' - '.str_random(3);
            if (!isset($model->Date)) $model->Date = now()->toDateTimeString();
        });
    }

    
    public function ETHCurrencyObj() { return $this->belongsTo("App\Core\Ethereum\Entities\ETHCurrency", "Currency", "Oid"); }
    public function TravelDetails() { return $this->hasMany("App\Core\Travel\Entities\TravelTransactionDetail", "TravelTransaction", "Oid"); }
    public function TravelPassengers() { return $this->hasMany("App\Core\Travel\Entities\TravelPassenger", "TravelTransaction", "Oid"); }
    public function Passengers() { return $this->hasMany("App\Core\Ferry\Entities\FerryPassenger", "FerTransaction", "Oid"); }
    public function DetailItems() { return $this->hasMany("App\Core\POS\Entities\PointOfSaleDetailItem", "PointOfSale", "Oid"); }
    public function Journals() { return $this->hasMany("App\Core\Accounting\Entities\Journal", "PointOfSale", "Oid"); }
    public function Stocks() { return $this->hasMany("App\Core\Trading\Entities\TransactionStock", "PointOfSale", "Oid"); }
    public function ETickets(){ return $this->hasMany("App\Core\POS\Entities\ETicket", "PointOfSale", "Oid");}
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    public function SupplierObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'Supplier', 'Oid'); }
    public function CurrencyRateObj() { return $this->belongsTo('App\Core\Master\Entities\CurrencyRate', 'CurrencyRate', 'Oid'); }
    public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
    public function CustomerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'Customer', 'Oid'); }
    public function AgentObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'Agent', 'Oid'); }
    public function POSSessionObj() { return $this->belongsTo('App\Core\POS\Entities\POSSession', 'POSSession', 'Oid'); }
    public function PointOfSaleTypeObj() { return $this->belongsTo('App\Core\Internal\Entities\PointOfSaleType', 'PointOfSaleType', 'Oid'); }
    public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
    public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
    public function Employee2Obj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee2', 'Oid'); }
    public function WarehouseObj() { return $this->belongsTo('App\Core\Master\Entities\Warehouse', 'Warehouse', 'Oid'); }
    public function TaxObj() { return $this->belongsTo('App\Core\Master\Entities\Tax', 'Tax', 'Oid'); }
    public function PurchaseCurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'PurchaseCurrency', 'Oid'); }
    public function POSTableObj() { return $this->belongsTo('App\Core\POS\Entities\POSTable', 'POSTable', 'Oid'); }
    public function PaymentCurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "PaymentCurrency", "Oid"); }
    public function PaymentMethodObj() { return $this->belongsTo("App\Core\Master\Entities\PaymentMethod", "PaymentMethod", "Oid"); }
    public function PaymentMethod2Obj() { return $this->belongsTo('App\Core\Master\Entities\PaymentMethod', 'PaymentMethod2', 'Oid'); }
    public function PaymentMethod3Obj() { return $this->belongsTo('App\Core\Master\Entities\PaymentMethod', 'PaymentMethod3', 'Oid'); }
    public function PaymentMethod4Obj() { return $this->belongsTo('App\Core\Master\Entities\PaymentMethod', 'PaymentMethod4', 'Oid'); }
    public function PaymentMethod5Obj() { return $this->belongsTo('App\Core\Master\Entities\PaymentMethod', 'PaymentMethod5', 'Oid'); }
    public function PaymentMethodChangesObj() { return $this->belongsTo('App\Core\Master\Entities\PaymentMethod', 'PaymentMethodChanges', 'Oid'); }
    public function ItemContentObj() { return $this->belongsTo('App\Core\Master\Entities\ItemContent', 'ItemContent', 'Oid'); }
    public function CompanySourceObj() { return $this->belongsTo('App\Core\Master\Entities\Company', 'CompanySource', 'Oid'); }
    public function POSSessionPreviousObj() { return $this->belongsTo('App\Core\POS\Entities\POSSession', 'POSSessionPrevious', 'Oid'); }
    public function ProjectObj() { return $this->belongsTo('App\Core\Master\Entities\Project', 'Project', 'Oid'); }
    public function PointOfSaleReturnObj() { return $this->belongsTo('App\Core\POS\Entities\PointOfSale', 'PointOfSaleReturn', 'Oid'); }
    
                
    public function SalesInvoices() { return $this->hasMany('App\Core\Trading\Entities\SalesInvoice', 'PointOfSale', 'Oid'); }
    public function Details() { return $this->hasMany('App\Core\POS\Entities\PointOfSaleDetail', 'PointOfSale', 'Oid'); }
    public function Logs() { return $this->hasMany('App\Core\POS\Entities\PointOfSaleLog', 'PointOfSale', 'Oid'); }
    

}