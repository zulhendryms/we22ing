<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Base\Traits\Activable;

class Collection extends BaseModel {
    use BelongsToCompany, Activable;
    protected $table = 'poscollection';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function Items() { return $this->belongsToMany("App\Core\Master\Entities\Item", "poscollectioncollections_mstitemitems", "Collections", "Items"); }
}