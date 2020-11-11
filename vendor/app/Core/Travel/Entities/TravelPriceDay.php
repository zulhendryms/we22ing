<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelPriceDay extends BaseModel {
    protected $table = 'trvpriceday';
    use BelongsToCompany;

    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid"); }
    public function TravelTypeObj() { return $this->belongsTo("App\Core\Master\Entities\TravelType", "TravelType", "Oid"); }
}