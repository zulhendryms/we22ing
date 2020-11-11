<?php

namespace App\Core\PointOfSale\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class POSETicketUpload extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'poseticketupload';                
    
public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
public function ItemParentObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'ItemParent', 'Oid'); }
public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }
public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
public function AccountObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'Account', 'Oid'); }
public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
public function WarehouseObj() { return $this->belongsTo('App\Core\Master\Entities\Warehouse', 'Warehouse', 'Oid'); }
public function ItemContentObj() { return $this->belongsTo('App\Core\Master\Entities\ItemContent', 'ItemContent', 'Oid'); }

    

}