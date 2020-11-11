<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;


class CompanyCreditCard extends BaseModel {
    use Activable, BelongsToCompany;
    protected $table = 'companycreditcard';

    public function BusinessPartner() { return $this->hasMany("App\Core\Master\Entities\BusinessPartner", "CompanyCreditCard", "Oid"); }
    public function EmployeeObj() { return $this->belongsTo("App\Core\Master\Entities\Employee", "Employee", "Oid"); }
}