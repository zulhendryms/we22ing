<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class CompanyItemContent extends BaseModel {
    use Activable, BelongsToCompany;
    protected $table = 'companyitemcontent';

    public function CompanyObj() { return $this->belongsTo('App\Core\Master\Entities\Company', 'Company', 'Oid'); }
    public function ItemContentObj() { return $this->belongsTo('App\Core\Master\Entities\ItemContent', 'ItemContent', 'Oid'); }
    public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
    public function ItemTypeObj() { return $this->belongsTo('App\Core\Internal\Entities\ItemType', 'ItemType', 'Oid'); }
    public function CompanyItemTypeObj() { return $this->belongsTo('App\Core\Master\Entities\CompanyItemType', 'CompanyItemType', 'Oid'); }
    public function CompanySupplierObj() { return $this->belongsTo('App\Core\Master\Entities\Company', 'CompanySupplier', 'Oid'); }
    public function BusinessPartnerCustomerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartnerCustomer', 'Oid'); }

}