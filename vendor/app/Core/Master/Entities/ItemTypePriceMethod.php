<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class ItemTypePriceMethod extends BaseModel {
    use Activable, BelongsToCompany;
    protected $table = 'mstpricemethoditemtype';

    public function ItemTypeObj() { return $this->belongsTo("App\Core\Internal\Entities\ItemType", "ItemType", "Oid"); }
    public function SalesAddMethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAddMethod", "Oid"); }
    public function SalesAdd1MethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAdd1Method", "Oid"); }
    public function SalesAdd2MethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAdd2Method", "Oid"); }
    public function SalesAdd3MethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAdd3Method", "Oid"); }
    public function SalesAdd4MethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAdd4Method", "Oid"); }
    public function SalesAdd5MethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAdd5Method", "Oid"); }

}