<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelPurchaseInvoice extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvpurchaseinvoice';

    public function BusinessPartnerObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid");
    }

    public function AccountObj()
    {
        return $this->belongsTo("App\Core\Accounting\Entities\Account", "Account", "Oid");
    }

    public function Journals()  
    {
        return $this->hasMany("App\Core\Accounting\Entities\Journal", "TravelPurchaseInvoice", "Oid");
    }
}