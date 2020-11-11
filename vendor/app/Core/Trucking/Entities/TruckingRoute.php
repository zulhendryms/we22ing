<?php

namespace App\Core\Trucking\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TruckingRoute extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trcroute';                
    
public function CityToObj() { return $this->belongsTo('App\Core\Master\Entities\City', 'CityTo', 'Oid'); }
public function CityFromObj() { return $this->belongsTo('App\Core\Master\Entities\City', 'CityFrom', 'Oid'); }
public function RouteReturnObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingRoute', 'RouteReturn', 'Oid'); }

}