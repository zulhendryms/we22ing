<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Base\Traits\Activable;

class FerryRouteGroup extends BaseModel {
    use BelongsToCompany, Activable;

    protected $table = 'ferroutegroup';

    /**
     * Get routes of the routegroup
     */
    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function Routes()
    {
        return $this->hasMany("App\Core\Ferry\Entities\FerryRoute", "RouteGroup", "Oid");
    }
}