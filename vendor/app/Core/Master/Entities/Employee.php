<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Employee extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstemployee';
            
public function UserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid'); }
public function EmployeePositionObj() { return $this->belongsTo('App\Core\Master\Entities\EmployeePosition', 'EmployeePosition', 'Oid'); }
public function TravelGuideGroupObj() { return $this->belongsTo('App\Core\Travel\Entities\TravelGuideGroup', 'TravelGuideGroup', 'Oid'); }
public function CityObj() { return $this->belongsTo('App\Core\Master\Entities\City', 'City', 'Oid'); }
public function DepartmentObj() { return $this->belongsTo('App\Core\Master\Entities\Department', 'Department', 'Oid'); }
public function EthnicObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceEthnic', 'Ethnic', 'Oid'); }
public function OutsourceCompanyObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'OutsourceCompany', 'Oid'); }
public function JobTitleObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceJobTitle', 'JobTitle', 'Oid'); }
public function JobLocationObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceJobLocation', 'JobLocation', 'Oid'); }
public function ProjectObj() { return $this->belongsTo('App\Core\Master\Entities\Project', 'Project', 'Oid'); }
public function TaxMaritalStatusObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceTaxMaritalStatus', 'TaxMaritalStatus', 'Oid'); }
public function TaxGroupObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceTaxGroup', 'TaxGroup', 'Oid'); }
public function InsuranceGroupObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceInsuranceGroup', 'InsuranceGroup', 'Oid'); }
public function SalaryGradeObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceSalaryGrade', 'SalaryGrade', 'Oid'); }
public function AttendanceGroupObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceAttendanceGroup', 'AttendanceGroup', 'Oid'); }
public function LeaveGroupObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceLeaveGroup', 'LeaveGroup', 'Oid'); }
public function OvertimeSetupGroupObj() { return $this->belongsTo('App\Core\HumanResource\Entities\HumanResourceOvertimeSetupGroup', 'OvertimeSetupGroup', 'Oid'); }


public function Documents() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceEmployeeDocument', 'Employee', 'Oid'); }
public function Banks() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceEmployeeBank', 'Employee', 'Oid'); }
public function Educations() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceEmployeeEducation', 'Employee', 'Oid'); }
public function EmployeeHistory() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceEmployeeHistory', 'Employee', 'Oid'); }
public function Mutations() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceEmployeeMutation', 'Employee', 'Oid'); }
public function Letters() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceEmployeeLetter', 'Employee', 'Oid'); }
public function EmploymentHistory() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceEmploymentHistory', 'Employee', 'Oid'); }
public function Contracts() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceEmployeeContract', 'Employee', 'Oid'); }
public function Contacts() { return $this->hasMany('App\Core\HumanResource\Entities\HumanResourceEmployeeContact', 'Employee', 'Oid'); }

}