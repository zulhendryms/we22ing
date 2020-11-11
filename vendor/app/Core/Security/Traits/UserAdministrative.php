<?php
namespace App\Core\Security\Traits;

use Illuminate\Support\Facades\DB;

trait UserAdministrative 
{
    public function isAdmin()
    {
        $query = "SELECT p.Oid FROM userusers_roleroles ur 
        INNER JOIN permissionpolicyrole p ON ur.Roles = p.Oid
        WHERE ur.Users = '{$this->Oid}'
        AND p.IsAdministrative IS TRUE";
        return !empty(DB::select($query));
    }

    public function isStaff()
    {
        return !empty(DB::select("SELECT ur.OID FROM userusers_roleroles ur WHERE ur.Users = '{$this->Oid}'"));
    }

    private function checkPermission($code, $type)
    {
        $field = [];
        switch($type) {
            case 'Add':
                $field = ['CreateState'];
                break;
            case 'Edit':
                $field = ['WriteState'];
                break;
            case 'Delete':
                $field = ['DeleteState'];
                break;
            case 'Navigate':
                $field = ['NavigateState'];
                break;
        }

        $where = '';

        foreach ($field as $f) {
            if ($where != '') $where .= ' AND ';
            $where .= "$f = 1";
        }

        $code = 'Cloud_ERP.Module.BusinessObjects.'.$code;

        return !empty(DB::select("SELECT * FROM permissionpolicytypepermissionsobject p 
        INNER JOIN permissionpolicyrole p1 ON p.Role = p1.Oid
        INNER JOIN userusers_roleroles ur ON ur.Roles = p1.Oid
        INNER JOIN user u ON ur.Users = u.Oid
        WHERE u.Oid = '{$this->Oid}' AND 
        p.TargetType = '{$code}' AND {$where}"));
    }

    public function allowAdd($code)
    {
        return $this->checkPermission($code, 'Add');
    }

    public function allowEdit($code)
    {
        return $this->checkPermission($code, 'Edit');
    }

    public function allowDelete($code)
    {
        return $this->checkPermission($code, 'Delete');
    }

    public function allowNavigate($code)
    {
        return $this->checkPermission($code, 'Navigate');
    }
}