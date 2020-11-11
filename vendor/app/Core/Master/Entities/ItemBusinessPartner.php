<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ItemBusinessPartner extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstitembusinesspartner';                
    
    public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }
    public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
    
                    
    public function Details() { return $this->hasMany('App\Core\Master\Entities\ItemBusinessPartnerDetail', 'ItemBusinessPartner', 'Oid'); }
}