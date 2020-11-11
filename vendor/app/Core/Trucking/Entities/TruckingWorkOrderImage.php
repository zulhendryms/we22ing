<?php

namespace App\Core\Trucking\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TruckingWorkOrderImage extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trcworkorderimage';

    public function TruckingWorkOrderObj()
    {
        return $this->belongsTo('App\Core\Trucking\Entities\TruckingWorkOrder', 'TruckingWorkOrder', 'Oid');
    }
}
