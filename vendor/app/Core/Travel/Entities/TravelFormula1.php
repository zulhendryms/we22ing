<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelFormula1 extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvformula1';

    public function fields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w'=> 0,   'r'=>0, 't'=>'text',    'n'=>'Oid',];
        $fields[] = ['w'=> 180, 'r'=>1, 't'=>'text',    'n'=>'Code',];
        $fields[] = ['w'=> 250, 'r'=>1, 't'=>'text',    'n'=>'Name',];
        $fields[] = ['w'=> 200, 'r'=>1, 't'=>'combo',   'n'=>'BusinessPartner',    'f'=>'bp.Name',];
        $fields[] = ['w'=> 200, 'r'=>0, 't'=>'text',    'n'=>'CompareMethod',];
        $fields[] = ['w'=> 200, 'r'=>0, 't'=>'text',    'n'=>'Criteria',];
        $fields[] = ['w'=> 200, 'r'=>1, 't'=>'combo',   'n'=>'Item',    'f'=>'i.Name',];
        $fields[] = ['w'=> 200, 'r'=>1, 't'=>'combo',   'n'=>'ItemGroup',    'f'=>'ig.Name',];
        $fields[] = ['w'=> 90,  'r'=>1, 't'=>'combo',   'n'=>'Currency',        'f'=>'c.Code',];
        $fields[] = ['w'=> 90, 'r'=>0, 't'=>'int',      'n'=>'AmountCost',];
        $fields[] = ['w'=> 200, 'r'=>0, 't'=>'text',    'n'=>'CalculationMethod',];
        
        return $fields;
    }
    
    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function ItemObj(){ return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");}  
    public function ItemGroupObj(){ return $this->belongsTo("App\Core\Master\Entities\ItemGroup", "ItemGroup", "Oid");}    
    public function CurrencyObj(){ return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid");}    
    public function Details() { return $this->hasMany("App\Core\Travel\Entities\TravelFormula1Detail", "TravelFormula1","Oid"); }
}