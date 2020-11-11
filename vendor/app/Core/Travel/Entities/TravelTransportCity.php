<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelTransportCity extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvtransportcity';

public function CityObj() { return $this->belongsTo('App\Core\Master\Entities\City', 'City', 'Oid'); }

        }