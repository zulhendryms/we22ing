<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelAllotmentCutoff extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvallotmentcutoff';

    public function TravelAllotmentObj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelAllotment", "TravelAllotment", "Oid");
    }
}
