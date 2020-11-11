<?php

namespace App\Core\POS\Traits;

trait ExtendsPointOfSale
{
    public function PointOfSaleObj()
    {
        return $this->hasOne("App\Core\POS\Entities\PointOfSale", "Oid", "Oid");
    }
}