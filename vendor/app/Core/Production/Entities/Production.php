<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Production extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdproduction';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function ProductionOrderItemDetailObj() { return $this->belongsTo("App\Core\Production\Entities\ProductionOrderItemDetail", "ProductionOrderItemDetail", "Oid"); }
    public function ProductionProcessObj() { return $this->belongsTo("App\Core\Production\Entities\ProductionProcess", "ProductionProcess", "Oid"); }
    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
}