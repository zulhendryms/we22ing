<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelFormula1Detail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvformula1detail';

    public function TravelFormula1Obj(){ return $this->belongsTo("App\Core\Travel\Entities\TravelFormula1", "TravelFormula1", "Oid");}

}