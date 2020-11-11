<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelContractGuide extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trvcontractguide';

    public function TravelTourGuideObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelGuideGroup', 'TravelGuideGroup', 'Oid');
    }
    public function CurrencyObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid');
    }
}
