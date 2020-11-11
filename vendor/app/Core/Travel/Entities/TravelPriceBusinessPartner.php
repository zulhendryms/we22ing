<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelPriceBusinessPartner extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trvpricebusinesspartner';

    public function BusinessPartnerGroupObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\BusinessPartnerGroup', 'BusinessPartnerGroup', 'Oid');
    }
    public function ItemTypeObj()
    {
        return $this->belongsTo('App\Core\Internal\Entities\ItemType', 'ItemType', 'Oid');
    }
}
