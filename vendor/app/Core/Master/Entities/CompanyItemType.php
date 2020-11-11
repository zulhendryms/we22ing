<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class CompanyItemType extends BaseModel {
    use Activable, BelongsToCompany;
    protected $table = 'companyitemtype';

    public function CompanyObj() { return $this->belongsTo('App\Core\Master\Entities\Company', 'Company', 'Oid'); }
    public function ItemTypeObj() { return $this->belongsTo('App\Core\Internal\Entities\ItemType', 'ItemType', 'Oid'); }
    public function CompanySupplierObj() { return $this->belongsTo('App\Core\Master\Entities\Company', 'CompanySupplier', 'Oid'); }
    public function CompanySourceObj() { return $this->belongsTo('App\Core\Master\Entities\Company', 'CompanySource', 'Oid'); }

}