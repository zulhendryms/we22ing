<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionUserProcess extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prduserprocess';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
    public function ProductionProcessObj() { return $this->belongsTo("App\Core\Production\Entities\ProductionProcess", "ProductionProcess", "Oid"); }
    
}