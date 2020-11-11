<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\POS\Entities\POSPriceAge;

class TravelItemPackage extends BaseModel 
{
    protected $gcrecord = false;
    protected $table = 'trvitempackage';

    public function Details()
    {
        return $this->hasMany("App\Core\Travel\Entities\TravelItemPackageDetail", "TravelItemPackage", "Oid");
    }
    public function PriceAges()
    {
        return $this->hasMany("App\Core\POS\Entities\POSPriceAge", "Item", "Oid");
    }
}
