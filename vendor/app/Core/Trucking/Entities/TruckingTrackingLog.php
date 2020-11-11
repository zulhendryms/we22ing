<?php

namespace App\Core\Trucking\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TruckingTrackingLog extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trctrackinglog';                
            
public function TruckingPrimeMoverObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingPrimeMover', 'TruckingPrimeMover', 'Oid'); }
}