<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;


/**
 * @property-read boolean $IsMajestic
 */
class BusinessPartnerGroup extends BaseModel {
    use BelongsToCompany;
    protected $table = 'mstbusinesspartnergroup';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function BusinessPartnerRoleObj() { 
        return $this->belongsTo("App\Core\Internal\Entities\BusinessPartnerRole", "BusinessPartnerRole", "Oid"); 
    }
    public function BusinessPartnerAccountGroupObj() { 
        return $this->belongsTo("App\Core\Master\Entities\BusinessPartnerAccountGroup", "BusinessPartnerAccountGroup", "Oid"); 
    }
}