<?php

namespace App\Core\Internal\Services;

use App\Core\Internal\Entities\XPWeakReference;

class XPWeakReferenceService 
{
    public function create($object)
    {
        return XPWeakReference::create([
            'TargetKey' => '[Guid]\''.$object->getKey().'\'',
            'ObjectType' => 45,
            'TargetType' => $object->ObjectType
        ]);
    }
}