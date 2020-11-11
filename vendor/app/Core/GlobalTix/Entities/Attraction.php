<?php

namespace App\Core\GlobalTix\Entities;

use App\Core\Base\Entities\BaseModel;

class Attraction extends BaseModel {
    protected $gcrecord = false;
    public $incrementing = false;
    protected $author = false;
    public $timestamps = false;
    
    protected $table = 'globaltixattraction';
    public function CountryObj()
    {
        return $this->belongsTo('App\Core\Internal\Entities\Country', 'CountryName','Name');
    }
    
    public function ItemObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Item", "Item");
    }

    public function ItemECommerces() { return $this->hasMany("App\Core\Master\Entities\ItemECommerce", "Item","Item"); }
    
}