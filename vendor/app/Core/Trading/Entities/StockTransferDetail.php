<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class StockTransferDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trdstocktransferdetail';

    public function StockTransferObj()  { return $this->belongsTo("App\Core\Trading\Entities\StockTransfer", "StockTransfer", "Oid"); }
    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");}
    public function ItemUnitObj() { return $this->belongsTo("App\Core\Master\Entities\ItemUnit", "ItemUnit", "Oid");}
}