<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class ItemGroup extends BaseModel {
    use Activable, BelongsToCompany;
    // use Activable;
    protected $table = 'mstitemgroup';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    
    public function ItemTypeObj() { return $this->belongsTo("App\Core\Internal\Entities\ItemType", "ItemType", "Oid"); }
    public function ItemAccountGroupObj() { return $this->belongsTo("App\Core\Master\Entities\ItemAccountGroup", "ItemAccountGroup", "Oid"); }
    public function Items() { return $this->belongsToMany("App\Core\Master\Entities\Item", "mstitemitems_mstitemgroupgroups", "Groups", "Items"); }
    public function ItemGroups() { return $this->hasMany("App\Core\Master\Entities\ItemGroup", "Parent", "Oid"); }
    public function ParentObj() { return $this->belongsTo("App\Core\Master\Entities\ItemGroup", "Parent", "Oid"); }
    public function ItemPriceMethodObj() { return $this->hasOne("App\Core\Master\Entities\ItemPriceMethod", "Oid", "ItemPriceMethod"); }
}