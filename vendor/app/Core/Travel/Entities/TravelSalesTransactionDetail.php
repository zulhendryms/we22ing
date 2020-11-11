<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelSalesTransactionDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvsalestransactiondetail';

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if (!isset($model->Code)) $model->Code = now()->format('ymdHis').' - '.str_random(3);
        });
    }

    public function __get($key)
    {
        switch ($key) {
            case "IsPurchase":
                return $this->Type == 1 || $this->Type == 3;
            case "IsSales":
                return $this->Type == 3;
        }
        return parent::__get($key);
    }

    public function TravelSalesTransactionObj() { return $this->belongsTo("App\Core\Travel\Entities\TravelSalesTransaction", "TravelSalesTransaction", "Oid"); }
    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function AccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "Account", "Oid"); }
    public function StatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid"); }
    public function CommStatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "CommStatus", "Oid"); }
    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid"); }
    public function Journals() { return $this->hasMany("App\Core\Accounting\Entities\Journal", "TravelSalesTransactionDetail", "Oid"); }
    public function TravelTransactionAllotments() { return $this->hasMany("App\Core\Travel\Entities\TravelTransactionAllotment", "TravelSalesTransactionDetail", "Oid"); }
}