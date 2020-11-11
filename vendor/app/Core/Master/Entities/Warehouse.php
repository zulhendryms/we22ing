<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Warehouse extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'mstwarehouse';

    public function Journals(){return $this->hasMany('App\Core\Accounting\Entities\Journal', 'Warehouse', 'Oid');}
}
