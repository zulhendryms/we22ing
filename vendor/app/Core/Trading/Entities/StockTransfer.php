<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class StockTransfer extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trdstocktransfer';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Code.' '.$this->Date;
        }
        return parent::__get($key);
    }

    public function WarehouseFromObj() { return $this->belongsTo("App\Core\Master\Entities\Warehouse", "WarehouseFrom", "Oid"); }
    public function WarehouseToObj() { return $this->belongsTo("App\Core\Master\Entities\Warehouse", "WarehouseTo", "Oid"); }
    public function StatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid"); }
    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
    public function Details()  { return $this->hasMany("App\Core\Trading\Entities\StockTransferDetail", "StockTransfer", "Oid"); }
    public function Journals() { return $this->hasMany("App\Core\Accounting\Entities\Journal", "StockTransfer", "Oid"); }
    public function Stocks() { return $this->hasMany("App\Core\Trading\Entities\TransactionStock", "StockTransfer", "Oid"); }
}