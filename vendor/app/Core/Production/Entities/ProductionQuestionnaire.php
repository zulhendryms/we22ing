<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionQuestionnaire extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'prdquestionnaire';



    public function Details()
    {
        return $this->hasMany('App\Core\Production\Entities\ProductionQuestionnaireDetail', 'ProductionQuestionnaire', 'Oid');
    }
    public function Processes()
    {
        return $this->hasMany('App\Core\Production\Entities\ProductionQuestionnaireProcess', 'ProductionQuestionnaire', 'Oid');
    }
}
