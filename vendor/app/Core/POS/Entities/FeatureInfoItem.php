<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class FeatureInfoItem extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'posfeatureinfoitem';

    public function FeatureInfoObj(){return $this->belongsTo("App\Core\POS\Entities\FeatureInfo", "POSFeatureInfo", "Oid");}
    public function POSFeatureInfoObj() { return $this->belongsTo('App\Core\POS\Entities\FeatureInfo', 'POSFeatureInfo', 'Oid'); }
    public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
    public function ItemContentObj() { return $this->belongsTo('App\Core\Master\Entities\ItemContent', 'ItemContent', 'Oid'); }
}