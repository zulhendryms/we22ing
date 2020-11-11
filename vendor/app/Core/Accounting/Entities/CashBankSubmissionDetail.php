<?php

    namespace App\Core\Accounting\Entities;

    use App\Core\Base\Entities\BaseModel;
    use App\Core\Base\Traits\BelongsToCompany;

    class CashBankSubmissionDetail extends BaseModel
    {
        use BelongsToCompany;
        protected $table = 'acccashbanksubmissiondetail';
        
        public function BusinessPartnerObj()
        {
            return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid');
        }
        public function CashBankSubmissionObj()
        {
            return $this->belongsTo('App\Core\Accounting\Entities\CashBankSubmission', 'CashBankSubmission', 'Oid');
        }
    }
