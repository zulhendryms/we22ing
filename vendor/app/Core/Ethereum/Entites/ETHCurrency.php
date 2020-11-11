<?php

namespace App\Core\Ethereum\Entities;

use App\Core\Base\Entities\BaseModel;
use Carbon\Carbon;

class ETHCurrency extends BaseModel {
    protected $table = 'ethcurrency';
    protected $gcrecord = false;

}