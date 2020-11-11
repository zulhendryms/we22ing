<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelItemPriceBusinessPartner extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trvitempricebusinesspartner';

    public function BusinessPartnerObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid');
    }
    public function BusinessPartnerGroupObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\BusinessPartnerGroup', 'BusinessPartnerGroup', 'Oid');
    }
    public function ItemObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid');
    }
    public function ItemContentObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\ItemContent', 'ItemContent', 'Oid');
    }
}
