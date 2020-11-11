<?php

namespace App\Core\Deal\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Base\Traits\Activable;
use App\Core\POS\Traits\ExtendsPointOfSale;

class DealTransaction extends BaseModel {

    use ExtendsPointOfSale;

    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;
    protected $table = 'dealtransaction';
    
    public function POSItemServiceObj()
    {
        return $this->belongsTo("App\Core\POS\Entities\ItemService", "POSItemService", "Oid");
    }
    public function ItemObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Item", "POSItemService", "Oid");
    }
}