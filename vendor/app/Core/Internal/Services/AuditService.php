<?php

namespace App\Core\Internal\Services;

use App\Core\POS\Entities\PointOfSaleLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class AuditService 
{
    public function create($object, $param)
    {
        $now = now();
        $param['User'] = $param['User'] ? $param['User']->Oid : null;
        $data = PointOfSaleLog::create(array_merge([
            'Company' => Auth::check() ? Auth::user()->Company : config('app.company_id'),
            'PointOfSale' => $object->Oid,
            'Name' => Auth::check() ? Auth::user()->UserName : 'Guest',
            'Status' => $object->Status ? $object->Status : null,
            'Date' => (clone $now)->addHours(company_timezone())->toDateTimeString(),
        ], $param));
    }
}