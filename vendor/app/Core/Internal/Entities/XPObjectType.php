<?php

namespace App\Core\Internal\Entities;

use Illuminate\Database\Eloquent\Model;


class XPObjectType extends Model
{
    protected $table = 'xpobjecttype';
    protected $primaryKey = 'OID';

    //POS
    const ITEM_POS_SERVICE = 79;

    // Travel
    const ITEM_TRAVEL_TOUR = 80;
    const ITEM_TRAVEL_HOTEL = 82;
    const ITEM_TRAVEL_TRANSPORT = 88;

    // Ferry
    const ITEM_FERRY_ATTRACTION = 76;
}