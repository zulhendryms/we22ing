<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelFormula1TransactionDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvformula1transactiondetail';

    public function TravelFormula1TransactionObj(){ return $this->belongsTo("App\Core\Travel\Entities\TravelFormula1Transaction", "TravelFormula1Transaction", "Oid");}
    public function ItemObj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");}  
    public function ItemGroupObj(){ return $this->belongsTo("App\Core\Master\Entities\ItemGroup", "ItemGroup", "Oid");}  
}