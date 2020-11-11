<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class POSPriceAge extends BaseModel {
    protected $table = 'pospriceage';
    use BelongsToCompany;

    public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
    public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }
    public function PurchaseCurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'PurchaseCurrency', 'Oid'); }
    public function SalesCurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'SalesCurrency', 'Oid'); }

}