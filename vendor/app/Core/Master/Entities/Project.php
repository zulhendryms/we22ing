<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Project extends BaseModel {
    use BelongsToCompany;
    protected $table = 'mstproject';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function fields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w'=> 0,   'r'=>0, 't'=>'text',    'n'=>'Oid',];
        $fields[] = ['w'=> 180, 'r'=>1, 't'=>'text',    'n'=>'Code',];
        $fields[] = ['w'=> 250, 'r'=>1, 't'=>'text',    'n'=>'Name',];
        $fields[] = ['w'=> 70,  'r'=>1, 't'=>'bool',    'n'=>'IsActive',];
        $fields[] = ['w'=> 200,  'r'=>1, 't'=>'combo',   'n'=>'BusinessPartner',        'f'=>'bp.Name',];
        $fields[] = ['w'=> 200, 'r'=>1, 't'=>'combo',   'n'=>'Employee',    'f'=>'e.Name',];
        $fields[] = ['w'=> 200, 'r'=>1, 't'=>'combo',   'n'=>'City',     'f'=>'cit.Name',];
        $fields[] = ['w'=> 200, 'r'=>0, 't'=>'date',    'n'=>'StartDate',];
        $fields[] = ['w'=> 200, 'r'=>0, 't'=>'date',    'n'=>'EndDate',];
        $fields[] = ['w'=> 90, 'r'=>0, 't'=>'int',     'n'=>'Qty',];
        $fields[] = ['w'=> 90, 'r'=>0, 't'=>'int',     'n'=>'Qty1',];
        $fields[] = ['w'=> 90, 'r'=>0, 't'=>'int',     'n'=>'Qty2',];
        $fields[] = ['w'=> 90, 'r'=>0, 't'=>'int',     'n'=>'Qty3',];
        $fields[] = ['w'=> 90, 'r'=>0, 't'=>'int',     'n'=>'Qty4',];
        $fields[] = ['w'=> 90, 'r'=>0, 't'=>'int',     'n'=>'Qty5',];
        return $fields;
    }

    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function EmployeeObj() { return $this->belongsTo("App\Core\Master\Entities\Employee", "Employee", "Oid"); }
    public function CityObj() { return $this->belongsTo("App\Core\Master\Entities\City", "City", "Oid"); }
    public function Users() { return $this->hasMany("App\Core\Master\Entities\ProjectUser", "Project", "Oid"); }
    public function CommandGroups() { return $this->hasMany("App\Core\RestApi\Entities\CommandGroup", "Project", "Oid"); }
    public function Variables() { return $this->hasMany("App\Core\RestApi\Entities\ProjectVariable", "Project", "Oid"); }
    public function APIVariables() { return $this->hasMany("App\Core\RestAPI\Entities\APIVariable", "Project", "Oid"); }
}