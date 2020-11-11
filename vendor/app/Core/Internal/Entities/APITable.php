<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class APITable extends BaseModel 
{
    protected $table = 'apitable';
    public function APITableParentObj() { return $this->belongsTo("App\Core\Internal\Entities\APITable", "APITableParent", "Oid"); }
    public function Details() { return $this->hasMany("App\Core\Internal\Entities\APITableField", "APITable", "Oid"); }
}