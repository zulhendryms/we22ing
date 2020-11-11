<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelType extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvtraveltype';

    public function __get($key)
    {
        switch ($key) {
            case "IsFIT":
                return $this->Code == 'FIT';
            case "IsGIT":
                return $this->Code == 'GIT';
        }
        return parent::__get($key);
    }

    public function scopeFIT($query) { return $query->where('Code', 'FIT'); }
    public function scopeGIT($query) { return $query->where('Code', 'GIT'); }
}