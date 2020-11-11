<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ECommerceField extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstecommercefield';

public function ECommerceObj() { return $this->belongsTo('App\Core\Master\Entities\ECommerce', 'ECommerce', 'Oid'); }
}