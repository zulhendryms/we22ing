<?php

namespace App\Core\Production\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ProductionQuestionnaireProcess extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'prdquestionnaireprocess';

    public function ProductionProcessObj()
    {
        return $this->belongsTo('App\Core\Production\Entities\ProductionProcess', 'ProductionProcess', 'Oid');
    }
    public function ProductionQuestionnaireObj()
    {
        return $this->belongsTo('App\Core\Production\Entities\ProductionQuestionnaireProcess', 'ProductionQuestionnaire', 'Oid');
    }
}
