<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelWebPromotion extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvwebpromotion';                

public function Details() { return $this->hasMany('App\Core\Travel\Entities\TravelWebPromotionDetail', 'TravelWebPromotion', 'Oid'); }

}
        