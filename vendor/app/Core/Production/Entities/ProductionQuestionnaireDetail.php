<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionQuestionnaireDetail extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'prdquestionnairedetail';

    public function ProductionQuestionnaireObj()
    {
        return $this->belongsTo('App\Core\Production\Entities\ProductionQuestionnaire', 'ProductionQuestionnaire', 'Oid');
    }
}
