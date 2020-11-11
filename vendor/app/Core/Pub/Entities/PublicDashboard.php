<?php

namespace App\Core\Pub\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class PublicDashboard extends BaseModel {
    use BelongsToCompany;
    protected $table = 'pubdashboard';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function CompanyObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "Company", "Oid"); }
    public function Details() { return $this->hasMany("App\Core\Pub\Entities\PublicDashboardDetail", "PublicDashboard", "Oid"); }
}
