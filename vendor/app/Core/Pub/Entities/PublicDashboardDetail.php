<?php

namespace App\Core\Pub\Entities;
use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PublicDashboardDetail extends BaseModel {
    protected $table = 'pubdashboarddetail';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function PublicDashboardObj() { return $this->belongsTo("App\Core\Pub\Entities\PublicDashboard", "PublicDashboard", "Oid"); }
}