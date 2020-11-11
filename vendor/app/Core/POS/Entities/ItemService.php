<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;

class ItemService extends BaseModel {
    protected $table = 'positemservice';
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
    public function Addresses() { return $this->belongsToMany("App\Core\Master\Entities\BusinessPartnerAddress", "positemservicepositemservices_mstbusinesspartneraddressaddresses", "POSItemServices", "Addresses"); }
}