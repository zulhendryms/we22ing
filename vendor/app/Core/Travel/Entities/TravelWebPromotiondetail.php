<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelWebPromotionDetail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvwebpromotiondetail';      

public function ItemObj() { return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid'); }
public function TravelWebPromotionObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelWebPromotion', 'TravelWebPromotion', 'Oid'); }

}
        