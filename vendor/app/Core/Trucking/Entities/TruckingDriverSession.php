<?php

namespace App\Core\Trucking\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TruckingDriverSession extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trcdriversession';

    public function TruckingPrimeMoverObj()
    {
        return $this->belongsTo('App\Core\Trucking\Entities\TruckingPrimeMover', 'TruckingPrimeMover', 'Oid');
    }
    public function UserObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid');
    }
    public function StatusObj()
    {
        return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid');
    }
}
