<?php

namespace App\Core\Pub\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PublicPostLike extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'pubpostlike';

    public function PublicPostObj()
    {
        return $this->belongsTo('App\Core\Pub\Entities\PublicPost', 'PublicPost', 'Oid');
    }
    public function CreatedByObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'CreatedBy', 'Oid');
    }
}
