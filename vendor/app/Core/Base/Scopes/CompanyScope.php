<?php

namespace App\Core\Base\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Core\Master\Entities\Company;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $logger = true;
        $table = $model->getTable();
        $user = Auth::user();
        if ($user) $company = Auth::user()->CompanySource; else $company = config('app.company_id');
        $company = Company::with('CompanySourceObj','CompanyParentObj')->where('Oid',$company)->first();
        $found = '';

        if (!$company) return null; // utk fix preview report, ga tau kenapa dari browser company nya null
        $found = $this->findObject($found, $company->ModuleGlobal, $model, 'GlobalAll');
        $found = $this->findObject($found, $company->ModuleGlobalCombo, $model, 'GlobalCombo');
        $found = $this->findObject($found, $company->ModuleGroup, $model, 'GroupAll');
        $found = $this->findObject($found, $company->ModuleGroupCombo, $model, 'GroupCombo');
        
        if ($company->Oid == $company->CompanySource) $type = 'Source';
        elseif ($company->Oid == $company->CompanyParent) $type = 'Parent';
        else $type = 'Detail';
        if ($found == '') {
            $filter = "Company = ".$company->Code." ".$company->Oid;
            $builder->where("Company",$company->Oid);
        } elseif ($type == 'Source') { // Source - GROUP COMBO
            $companies = Company::where('CompanySource', $company->Oid)->orWhere('Oid',$company->Oid)->pluck('Oid');
            $filter = "CompanySource =".$company->CompanySourceObj->Code;
            $builder->whereIn($model->getTable().'.Company', $companies);          
        } elseif ($type == 'Parent') { // Group - GROUP COMBO
            switch ($found) {
                case 'GlobalAll' || 'GlobalCombo':
                    $filter = "CompanyParent=".$company->CompanyParentObj->Code." & CompanySource=".$company->CompanySourceObj->Code;
                    $companies = Company::where('CompanyParent', $company->Oid)->orWhere('CompanySource',$company->CompanySource)->orWhere('Oid',$company->Oid)->pluck('Oid');
                    // if ($table == 'mstpaymentterm') dd($companies);
                    $builder->whereIn($model->getTable().'.Company', $companies);
                    break;
                case 'GroupAll' || 'GroupCombo':
                    $filter = "CompanyParent =".$company->CompanyParentObj->Code;
                    $companies = Company::where('CompanyParent', $company->Oid)->orWhere('Oid',$company->Oid)->pluck('Oid');
                    $builder->whereIn($model->getTable().'.Company', $companies);
                    break;
            }
        } elseif ($type == 'Detail') { // Group - GROUP COMBO
            switch ($found) {
                case 'GlobalAll' || 'GlobalCombo':
                    $filter = "Company =".$company->Code." / ".$company->CompanyParentObj->Code." / ".$company->CompanySourceObj->Code;
                    $companies = Company::where('Oid', $company->Oid)->orWhere('Oid',$company->CompanyParent)->orWhere('Oid',$company->CompanySource)->pluck('Oid');
                    $builder->whereIn($model->getTable().'.Company', $companies);
                    break;
                case 'GroupAll' || 'GroupCombo':
                    $filter = "Company =".$company->Code." / ".$company->CompanyParentObj->Code;
                    $companies = Company::where('Oid', $company->Oid)->orWhere('Oid',$company->CompanyParent)->pluck('Oid');
                    $builder->whereIn($model->getTable().'.Company', $companies);
                    break;
            }
        } else {
            //nofilter
        }

        if ($logger) logger('Scope '.$type." ".$model->getTable()." ".($found ?: 'NOT FOUND')." ".($filter ?: 'NO FILTER'));
    }
    private function findObject($found, $data, $model, $newFound) {
        if ($found != '') return $found;
        if ($data) $data = json_decode($data);
        if (!$data) return $found;
        foreach($data as $row) {            
            if ($row == 'all') return $newFound;
            if ($row == $model->getTable()) return $newFound;
        }
    }
}