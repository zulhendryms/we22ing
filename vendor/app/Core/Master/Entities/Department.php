<?php

    namespace App\Core\Master\Entities;

    use App\Core\Base\Entities\BaseModel;
    use App\Core\Base\Traits\BelongsToCompany;

    class Department extends BaseModel
    {
        use BelongsToCompany;
        protected $table = 'mstdepartment';
        
        public function Approval1Obj()
        {
            return $this->belongsTo('App\Core\Security\Entities\User', 'Approval1', 'Oid');
        }
        public function Approval2Obj()
        {
            return $this->belongsTo('App\Core\Security\Entities\User', 'Approval2', 'Oid');
        }
        public function Approval3Obj()
        {
            return $this->belongsTo('App\Core\Security\Entities\User', 'Approval3', 'Oid');
        }
        public function PurchaserObj()
        {
            return $this->belongsTo('App\Core\Security\Entities\User', 'Purchaser', 'Oid');
        }
        public function UserObj()
        {
            return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid');
        }
        public function UserNotification1Obj()
        {
            return $this->belongsTo('App\Core\Security\Entities\User', 'UserNotification1', 'Oid');
        }
        public function UserNotification2Obj()
        {
            return $this->belongsTo('App\Core\Security\Entities\User', 'UserNotification2', 'Oid');
        }
        public function UserNotification3Obj()
        {
            return $this->belongsTo('App\Core\Security\Entities\User', 'UserNotification3', 'Oid');
        }
    }
