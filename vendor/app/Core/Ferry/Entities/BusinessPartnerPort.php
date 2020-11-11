<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class BusinessPartnerPort extends BaseModel {
    use BelongsToCompany;
    protected $table = 'ferbusinesspartnerport';

    /**
     * Get the business partner of the port
     */
    public function BusinessPartnerObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid");
    }
}