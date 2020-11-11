<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Base\Traits\Activable;

class BusinessPartnerAddress extends BaseModel {
    protected $table = 'mstbusinesspartneraddress';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function POSItemServices() { return $this->belongsToMany("App\Core\Master\Entities\Item", "positemservicepositemservices_mstbusinesspartneraddressaddresses", "Addresses", "POSItemServices"); }
}