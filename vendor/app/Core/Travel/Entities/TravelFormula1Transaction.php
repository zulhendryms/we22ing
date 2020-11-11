<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelFormula1Transaction extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvformula1transaction';

    public function fields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w'=> 0,   'r'=>0, 't'=>'text',    'n'=>'Oid',];
        $fields[] = ['w'=> 180, 'r'=>1, 't'=>'text',    'n'=>'Code',];
        $fields[] = ['w'=> 250, 'r'=>1, 't'=>'text',    'n'=>'Name',];
        $fields[] = ['w'=> 200, 'r'=>1, 't'=>'combo',   'n'=>'BusinessPartner',    'f'=>'bp.Name',];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'date',    'n'=>'Date',];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'date',    'n'=>'UploadedAt',];
        $fields[] = ['w'=> 200, 'r'=>1, 't'=>'combo',   'n'=>'UploadedUser',    'f'=>'u.UserName',];
        
        return $fields;
    }
    
    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function UploadedUserObj() {return $this->belongsTo("App\Core\Security\Entities\User", "UploadedUser", "Oid"); }    
    public function Details() { return $this->hasMany("App\Core\Travel\Entities\TravelFormula1TransactionDetail", "TravelFormula1Transaction","Oid"); }
}