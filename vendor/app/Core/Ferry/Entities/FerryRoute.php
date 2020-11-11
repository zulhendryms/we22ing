<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Base\Traits\Activable;
use Awobaz\Compoships\Compoships;

class FerryRoute extends BaseModel {
    use Compoships;
    use Activable, BelongsToCompany;

    protected $table = 'ferroute';

    /**
     * Get the route's port from
     */
    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function PortFromObj() { return $this->belongsTo('App\Core\Ferry\Entities\FerryPort', 'PortFrom', 'Oid'); }
    public function PortToObj() { return $this->belongsTo('App\Core\Ferry\Entities\FerryPort', 'PortTo', 'Oid'); }
    public function RouteGroupObj() { return $this->belongsTo('App\Core\Ferry\Entities\FerryRouteGroup', 'RouteGroup', 'Oid'); }
    
}