<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class FerryPassenger extends BaseModel {
    use BelongsToCompany;
    protected $table = 'fertransactionpassenger';

    /**
     * Get the nationality of passenger
     */
    public function NationalityObj()
    {
        return $this->belongsTo("App\Core\Internal\Entities\Country", "Nationality", "Oid");
    }
}