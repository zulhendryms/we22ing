<?php

namespace App\AdminApi\System\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\Account;
use App\Core\Master\Entities\Employee;
use App\Core\Trading\Entities\PurchaseOrder;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Services\HttpService;
use App\Core\Internal\Services\AutoNumberService;

class DevelopmentController extends Controller
{
    private $httpService;
    protected $roleService;
    private $autoNumberService;
    public function __construct(
        RoleModuleService $roleService, 
        AutoNumberService $autoNumberService,
        HttpService $httpService)
    {
        $this->roleService = $roleService;
        $this->httpService = $httpService;
        $this->autoNumberService = $autoNumberService;
        $this->httpService->baseUrl('http://api1.ezbooking.co:888')->json();
    }

    public function globalData(Request $request) { 
        $employee = Employee::get()->count();
        $account = Account::get()->count();
        $purchaseOrder = PurchaseOrder::get()->count();
        $businessPartner = BusinessPartner::get()->count();

        return [
            'Employeee' => $employee,
            'Account' => $account,
            'PurchaseOrder' => $purchaseOrder,
            'BusinessPartner' => $businessPartner,
        ];

    }

    public function generateAutoNumber2() {
        $query = "SELECT tb.Name, tb.Code, tb.APITableGroup
            FROM ezb_server.apitablefield tbf 
            LEFT OUTER JOIN ezb_server.apitable tb ON tb.Oid = tbf.APITable
            WHERE tbf.Code = 'Code' AND 
            LEFT(tb.code,3) NOT IN ('sys','api','imp','not','shi','tbo','tab','use','wir','att','com','glo','hrs') AND
            tb.code NOT IN ('poscheck_subtotalbedadgdetail','posupload','traveltransaction','trvattraction','trvtransportitemprice','trvtransaction','trdpurchaserequestversion')
            AND tb.Code IS NOT NULL ORDER BY tb.Code";
        $data = DB::select($query);
        $result = "";
        foreach($data as $row) {
            $result = $result."'".$row->Code."' => 'App\Core\\".$row->APITableGroup."\Entities\\".$row->Name."',".PHP_EOL;
        }
        return $result;
    }

    public function listClass() {
        $query = "SELECT tb.Name, tb.Code, tb.APITableGroup
            FROM ezb_server.apitable tb 
            WHERE LEFT(tb.code,3) NOT IN ('api','imp','not','shi','tbo','tab','use','wir','att','com','glo','b_c','b_l','gro','fil','job','mig','ite','mod','oau','ana','aud','das','dev',
            'fai','hca','mya','myr','per','rep','ser','ses','tok','xpo','xpw') 
            AND tb.code NOT IN ('poscheck_subtotalbedadgdetail','accapinvoice','accarinvoice',
            'mstbusinesspartneraddressaddresses_dealitemdealitems','syscountryethcountries_ethitemtokenethitemtokens','sysfeaturepluginfeatureplugins_companycompanies'
            '')            
            ORDER BY tb.Code";
        $data = DB::select($query);
        $result = "";
        foreach($data as $row) {
            $result = $result."'".$row->Code."' => 'App\Core\\".$row->APITableGroup."\Entities\\".$row->Name."',".PHP_EOL;
        }
        return $result;
    }

    public function generateAutoNumber() {
        DB::update("UPDATE sysautonumbersetup s SET TableQuery = NULL WHERE s.TableQuery= 'mstitem'");
        DB::update("UPDATE sysautonumbersetup s SET TableQuery = 'mstitem' WHERE s.TargetType = 'Cloud_ERP.Module.BusinessObjects.Master.Item'");
        DB::insert("INSERT INTO sysautonumbersetup (Oid, Type, TargetType, FieldName, Digit, TableQuery)
            SELECT UUID(), 0, tb.Code, 'Code',6,tb.Code
            FROM ezb_server.apitablefield tbf 
            LEFT OUTER JOIN ezb_server.apitable tb ON tb.Oid = tbf.APITable
            LEFT OUTER JOIN sysautonumbersetup noset ON noset.TableQuery = tb.Code
            WHERE tbf.Code = 'Code' AND 
            LEFT(tb.code,3) NOT IN ('sys','api','imp','not','shi','tbo','tab','use','wir','att','com','glo','hrs') AND
            tb.code NOT IN ('poscheck_subtotalbedadgdetail','posupload','traveltransaction','trvattraction','trvtransportitemprice','trvtransaction','trdpurchaserequestversion')
            AND tb.Code IS NOT NULL AND noset.Oid IS NULL ORDER BY tb.Code");
        DB::delete('DELETE FROM sysautonumber');
        $data = AutoNumberSetup::whereNotNull('TableQuery')
            ->whereRaw("LEFT(TableQuery,3) NOT IN ('sys','api','imp','not','shi','tbo','tab','use','wir','att','com','glo','hrs')")
            ->whereNotIn('TableQuery',['poscheck_subtotalbedadgdetail','posupload','traveltransaction','trvattraction','trvtransportitemprice','trvtransaction','trdpurchaserequestversion'])
            ->get();
        $result = [];
        foreach($data as $row) {
            if (in_array(substr($row->TableQuery,0,3), ['sys','api','imp','not','shi','tbo','tab','use','wir','att','com','glo','hrs'])) continue;
            if ($row->Type == 0) {
                $query = "SELECT d.Company, CAST(MAX(d.Code) AS UNSIGNED) AS No, NULL as Prefix
                    FROM {$row->TableQuery} d 
                    INNER JOIN sysautonumbersetup s ON s.TableQuery = '{$row->TableQuery}' AND s.Type = 0
                    WHERE LENGTH(d.Code)=s.Digit AND CAST(d.Code AS UNSIGNED) > 0
                    GROUP BY d.Company";
            } else {
                $query = "SELECT d.Company, LEFT(d.Code,LENGTH(d.Code)-s.Digit) Prefix, CAST(MAX(RIGHT(d.Code,s.Digit)) AS UNSIGNED) AS No
                    FROM {$row->TableQuery} d 
                    INNER JOIN sysautonumbersetup s ON s.TableQuery = '{$row->TableQuery}' AND s.Type = 1
                    WHERE LEFT(RIGHT(d.Code,s.Digit+1),1) = '-'
                    GROUP BY d.Company, LEFT(d.Code,LENGTH(d.Code)-s.Digit)";
            }
            $rec = DB::select($query);
            // if ($row->TableQuery == 'trvpurchaseinvoice') dd(count($rec));
            if ($row->TableQuery == 'dev_pos') dd($query);
            if (count($rec) > 0) {
                foreach($rec as $record) {
                    $tmp = new AutoNumber();
                    $tmp->Company = $record->Company;
                    $tmp->Prefix = $record->Prefix;
                    $tmp->Number = $record->No;
                    $tmp->TableQuery = $row->TableQuery;
                    $tmp->save();
                    $result[] = $tmp;
                }
            }
        }
        return $result;
    }
}
