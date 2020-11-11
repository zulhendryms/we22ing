<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use Awobaz\Compoships\Compoships;

class FerryPricing extends BaseModel {
    use BelongsToCompany;
    use Compoships;
    protected $table = 'ferferrypricing';

    public function BusinessPartnerPortObj()
    {
        return $this->belongsTo("App\Core\Ferry\Entities\BusinessPartnerPort", "BusinessPartnerPort", "Oid");
    }

    /**
     * Get the route of the schedule
     */
    public function RouteObj()
    {
        return $this->belongsTo("App\Core\Ferry\Entities\Route", "Route", "Oid");
    }
}