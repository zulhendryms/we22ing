<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Base\Traits\Activable;

class FerryPort extends BaseModel {
    use Activable, BelongsToCompany;
    
    protected $table = 'ferport';

    /**
     * Get the city of the port
     */
    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function CityObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\City", "City", "Oid");
    }
}