<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelItemPackageDetail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvitempackagedetail';

    public function __get($property) 
    {
        switch ($property) {
            case 'IsNote':
                return $this->Type == 0;
            case 'IsInclude':
                return $this->Type == 1;
            case 'IsBenefit':
                return $this->Type == 2;
            case 'IsOptional':
                return $this->Type == 3;
            case 'IsCommission':
                return $this->Type == 4;
            case 'IsOther':
                return $this->Type == 5;
        }
        return parent::__get($property);
    }

    public function PriceAgeObj()
    {
        return $this->belongsTo("App\Core\POS\Entities\POSPriceAge", "PriceAge", "Oid");
    }

    public function PriceDayObj()
    {
        return $this->belongsTo("App\Core\POS\Entities\POSPriceDay", "PriceDay", "Oid");
    }

    public function ItemGroupObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\ItemGroup", "ItemGroup", "Oid");
    }
}
