<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class Region extends BaseModel {
    use Activable, BelongsToCompany;
    protected $table = 'mstregion';

    /**
     * Get the country of the city.
     */
    public function CountryObj()
    {
        return $this->belongsTo("App\Core\Internal\Entities\Country", "Country", "Oid");
    }

    /**
     * Get the timezone of the city.
     */
    public function TimezoneObj()
    {
        return $this->belongsTo("App\Core\Internal\Entities\Timezone", "Timezone", "Oid");
    }

    /**
     * Get the timezone of the city.
     */
    public function BusinessPartnerAddresses()
    {
        return $this->hasMany("App\Core\Master\Entities\BusinessPartnerAddress", "City", "Oid");
    }
}