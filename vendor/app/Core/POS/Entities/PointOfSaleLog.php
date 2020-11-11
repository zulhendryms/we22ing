<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PointOfSaleLog extends BaseModel {
    use BelongsToCompany;
    protected $table = 'pospointofsalelog';

public function PointOfSaleObj() { return $this->belongsTo('App\Core\POS\Entities\PointOfSale', 'PointOfSale', 'Oid'); }
public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }

}