<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;

class TravelItemTransport extends BaseModel {
    protected $table = 'trvitemtransport';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;

    public function ItemObj() { return $this->hasOne("App\Core\Master\Entities\Item", "Oid", "Oid"); }
    public function TravelTransportBrandObj() { return $this->belongsTo("App\Core\Travel\Entities\TravelTransportBrand", "TravelTransportBrand", "Oid"); }
}