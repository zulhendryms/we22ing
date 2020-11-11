<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PointOfSaleDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'pospointofsaledetail';


    public function ItemObj(){return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");}
    public function PointOfSaleObj(){return $this->belongsTo("App\Core\POS\Entities\PointOfSale", "PointOfSale", "Oid");}
    public function Passengers(){return $this->hasMany("App\Core\POS\Entities\PointOfSalePassenger", "PointOfSaleDetail", "Oid");}
    public function ETicketRedeems(){return $this->hasMany("App\Core\POS\Entities\ETicketRedeem", "PointOfSaleDetail", "Oid");}

    // public function ItemObj() { return $this->belongsTo('App\Core\Ferry\Entities\FerryItem', 'Item', 'Oid'); }
    public function ItemUnitObj() { return $this->belongsTo('App\Core\Master\Entities\ItemUnit', 'ItemUnit', 'Oid'); }
    




}