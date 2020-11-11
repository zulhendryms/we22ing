<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;

class ProductionItemGlass extends BaseModel {
    protected $table = 'prditemglass';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->ItemObj->Name.' - '.$this->ItemObj->Code;
        }
        return parent::__get($key);
    }
    
    public function ItemObj() { return $this->hasOne("App\Core\Master\Entities\Item", "Oid", "Oid"); }
    public function ProductionThicknessObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionThickness", "ProductionThickness", "Oid");}
}