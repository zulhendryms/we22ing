<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelSalesTransaction extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvsalestransaction';

    public function BusinessPartnerObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid");
    }

    public function AccountObj()
    {
        return $this->belongsTo("App\Core\Accounting\Entities\Account", "Account", "Oid");
    }

    public function Details()  
    {
        return $this->hasMany("App\Core\Travel\Entities\TravelSalesTransactionDetail", "TravelSalesTransaction", "Oid");
    }

    public function Journals()  
    {
        return $this->hasMany("App\Core\Accounting\Entities\Journal", "TravelSalesTransaction", "Oid");
    }

    public function TravelPackageObj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelPackage", "TravelPackage", "Oid");
    }
    
}