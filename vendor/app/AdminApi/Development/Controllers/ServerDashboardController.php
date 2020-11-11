<?php

namespace App\AdminApi\Development\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Internal\Entities\Module;
use Validator;

class ServerDashboardController extends Controller
{
    private $dbConnection;
    public function __construct()
    {
        $this->dbConnection = DB::connection('server');
    }

    public function functionGetTemplateData($criteria = '') {
        if ($criteria) $criteria = " AND Code IN (".$criteria.")";
        $query = "SELECT * FROM apidashboardquery WHERE GCRecord IS NULL ".$criteria." ORDER BY Name";
        return $this->dbConnection->select(DB::raw($query));
    }    

    public function functionGetTemplateCombo($type = 'combo',$code = null, $chartType = null) {
        $data = $this->functionGetTemplateData();
        $result = [];
        foreach($data as $row) {
            if ($code) if ($row->Code != $code) continue;
            if ($chartType) if ($row->ChartType != $chartType) continue;
            if ($type == 'combo') $result[] = [
                "Oid" => $row->Code,
                "Name" => $row->Name,
            ];
            else $result[] = [
                "Oid" => $row->Code,
                "Name" => $row->Name,
                "ChartType" => $row->ChartType,
                "Description" => $row->Description,
                "Query" => $row->Query,
            ];
        }
        return $result;
    }
}
