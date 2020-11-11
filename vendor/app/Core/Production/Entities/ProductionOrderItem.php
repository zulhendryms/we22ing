<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionOrderItem extends BaseModel {
    use BelongsToCompany;
    protected $table = 'prdorderitem';

    
    public function ProductionOrderObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionOrder", "ProductionOrder", "Oid");}
    public function ProductionShapeObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionShape", "ProductionShape", "Oid");}
    public function ItemProduct1Obj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "ItemProduct1", "Oid");}
    public function ItemGlass1Obj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "ItemGlass1", "Oid");}
    public function ItemProduct2Obj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "ItemProduct2", "Oid");}
    public function ItemGlass2Obj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "ItemGlass2", "Oid");}
    public function ItemProduct3Obj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "ItemProduct3", "Oid");}
    public function ItemGlass3Obj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "ItemGlass3", "Oid");}
    public function ItemProduct4Obj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "ItemProduct4", "Oid");}
    public function ItemGlass4Obj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "ItemGlass4", "Oid");}
    public function ItemProduct5Obj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "ItemProduct5", "Oid");}
    public function ItemGlass5Obj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "ItemGlass5", "Oid");}
    public function OrderItemDetails() { return $this->hasMany("App\Core\Production\Entities\ProductionOrderItemDetail", "ProductionOrderItem","Oid"); }
    public function OrderItemOthers() { return $this->hasMany("App\Core\Production\Entities\ProductionOrderItemOther", "ProductionOrderItem","Oid"); }
    public function OrderItemProcess() { return $this->hasMany("App\Core\Production\Entities\ProductionOrderItemProcess", "ProductionOrderItem","Oid"); }
    public function OrderItemPictures() { return $this->hasMany("App\Core\Production\Entities\ProductionOrderItemPicture", "ProductionOrderItem","Oid"); }
    public function ProductionUnitConvertionObj(){ return $this->belongsTo("App\Core\Production\Entities\ProductionUnitConvertion", "ProductionUnitConvertion", "Oid");}
}