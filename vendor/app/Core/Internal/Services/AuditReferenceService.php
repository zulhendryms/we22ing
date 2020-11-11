<?php

namespace App\Core\Internal\Services;

use App\Core\Internal\Entities\AuditedObjectWeakReference;

class AuditReferenceService 
{
    private $xpWeakReferenceService;

    public function __construct(XPWeakReferenceService $xpWeakReferenceService)
    {
        $this->xpWeakReferenceService = $xpWeakReferenceService;
    }

    public function find($object)
    {
        return AuditedObjectWeakReference::where('GuidId', $object->getKey())->first();
    }

    public function create($object)
    {
        $weakReference = $this->xpWeakReferenceService->create($object);
        return $weakReference->AuditedObjectWeakReferenceObj()->create([
            // 'DisplayName' => $this->displayName,
            'GuidId' => $object->getKey(),
        ]);
    }
}