<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ItemPriceMethodCustom extends BaseModel {
    use BelongsToCompany;
    protected $table = 'mstitempricemethodcustom';

    public function ItemObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid"); }
    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function SalesCurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "SalesCurrency", "Oid"); }
    public function ItemPriceMethodObj() { return $this->belongsTo("App\Core\Master\Entities\ItemPriceMethod", "ItemPriceMethod", "Oid"); }

}