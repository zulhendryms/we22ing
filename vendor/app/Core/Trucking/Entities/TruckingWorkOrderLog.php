<?php

namespace App\Core\Trucking\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TruckingWorkOrderLog extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trcworkorderlog';

    public function UserObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid');
    }
    public function TruckingWorkOrderObj()
    {
        return $this->belongsTo('App\Core\Trucking\Entities\TruckingWorkOrder', 'TruckingWorkOrder', 'Oid');
    }

    public function UserDriverObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'UserDriver', 'Oid'); }
    public function TruckingPrimeMoverObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingPrimeMover', 'TruckingPrimeMover', 'Oid'); }
    public function TruckingTrailerObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingTrailer', 'TruckingTrailer', 'Oid'); }
    public function TruckingDriverCodeObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingDriverCode', 'TruckingDriverCode', 'Oid'); }
    public function TruckingSalesCodeObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingSalesCode', 'TruckingSalesCode', 'Oid'); }
}
