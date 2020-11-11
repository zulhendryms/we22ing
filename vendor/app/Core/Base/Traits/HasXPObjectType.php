<?php

namespace App\Core\Base\Traits;

use App\Core\Internal\Entities\XPObjectType;

trait HasXPObjectType
{
    public function getObjectTypeAttribute($value)
    {
        if (isset($value)) return $value;
        return; //TODO: UNTUK EZPOS BISA JALAN
        return $this->XPObjectTypeObj->OID;
    }

    public function getTargetType()
    {
        return static::XP_TARGET_TYPE;
    }

    public function getXPObjectTypeObjAttribute()
    {
        if ($this->getOriginal('ObjectType') != null) {
            return XPObjectType::find($this->getOriginal('ObjectType'));
        }
        return XPObjectType::where('TypeName', $this->getTargetType())->first();
    }
    
    public static function getXPObjectType()
    {
        return XPObjectType::where('TypeName', static::XP_TARGET_TYPE)->first();
    }
}