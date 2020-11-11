<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ETicketNumber extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'poseticketnumber';

    public function ItemObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid');
    }
    public function TaskTempObj()
    {
        return $this->belongsTo('App\Core\POS\Entities\ETicketNumber', 'TaskTemp', 'Oid');
    }
    public function ItemContentObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\ItemContent', 'ItemContent', 'Oid');
    }
}
