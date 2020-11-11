<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionPrice extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'prdprice';
    
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    public function ItemGroupObj() { return $this->belongsTo('App\Core\Master\Entities\ItemGroup', 'ItemGroup', 'Oid'); }
    public function ItemProductObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'ItemProduct', 'Oid'); }

        
    public function Details() { return $this->hasMany('App\Core\Production\Entities\ProductionPriceDetail', 'ProductionPrice', 'Oid'); }

}
