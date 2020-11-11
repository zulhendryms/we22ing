<?php

namespace App\AdminApi\Development\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Internal\Entities\Module;
use Validator;

class ServerCRUDController extends Controller
{
    private $dbConnection;
    public function __construct()
    {
        $this->dbConnection = DB::connection('server');
    }

    public function convertJSon(Request $request) {
        // $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF
        $str = json_encode(json_decode($request->getContent())); //WILLIAM ZEF
        $str = str_replace("\/","/",$str);
        $str = str_replace("{","[",$str);
        $str = str_replace("}","]",$str);
        $str = str_replace(":","=>",$str);
        return $str;
    }

    public function listClass() {
        $query = "SELECT tb.Name, tb.Code, tb.APITableGroup
            FROM apitable tb 
            WHERE LEFT(tb.code,3) NOT IN ('api','imp','not','shi','tbo','tab','wir','att','glo','b_c','b_l','gro','fil','job','mig','ite','mod','oau','ana','aud','das','dev',
            'fai','hca','mya','myr','per','rep','ser','ses','tok','xpo','xpw') 
            AND tb.code NOT IN ('poscheck_subtotalbedadgdetail','accapinvoice','accarinvoice',
            'mstbusinesspartneraddressaddresses_dealitemdealitems','syscountryethcountries_ethitemtokenethitemtokens','sysfeaturepluginfeatureplugins_companycompanies'
            '')            
            ORDER BY tb.Code";
        $data = $this->dbConnection->select(DB::raw($query));
        $result = "";
        foreach($data as $row) {
            $result = $result."'".$row->Code."' => 'App\Core\\".$row->APITableGroup."\Entities\\".$row->Name."',".PHP_EOL;
        }
        return $result;
    }

    public function functionGetFieldsFromTable($table) {     
        $criteriaCompany = " AND (LOWER(tb.Code) = '".strtolower($table)."' OR LOWER(tb.Name) = '".strtolower($table)."')";

        $query = "SELECT tbf.*, 
            tbc.Code AS TableComboCode, tbc.Name AS TableComboCode, tbc.Combo1 AS TableComboField,
            tbf.ComboSourceManual, tbc.Combo1 AS TableFieldDisplay, tbc.FormType AS TableFormType, tbc.ReportJoinTable
            FROM apitablefield tbf 
            LEFT OUTER JOIN apitable tb ON tbf.APITable = tb.Oid
            LEFT OUTER JOIN apitable tbc ON tbf.APITableCombo = tbc.Oid
            WHERE tbf.GCRecord IS NULL 
            AND tbf.Code NOT IN ('Oid','id','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','UpdatedBy')
            {$criteriaCompany} ORDER BY Sequence";
        return $this->dbConnection->select(DB::raw($query));
    }

    public function functionGetReportParent($criteria) {
        $query = "SELECT * FROM sysmodule WHERE Code = '".$criteria."'";
        return $this->dbConnection->select(DB::raw($query));
    }

    public function functionGetReport($criteria) {
        $query = "SELECT * FROM apireport WHERE Code = '".$criteria."'";
        return $this->dbConnection->select(DB::raw($query));
    }

    public function functionGetReportTableJoin($criteria) {
        $query = "SELECT * FROM apitable WHERE ReportJoinTable IS NOT NULL AND Code IN (".$criteria.")";
        return $this->dbConnection->select(DB::raw($query));
    }

    public function functionGetFieldsComboFromTable($table, $type, $tableData = null) {     
        $criteriaCompany = " AND (LOWER(tb.Code) = '".strtolower($table)."' OR LOWER(tb.Name) = '".strtolower($table)."')";
        if ($type == 'config') $criteria = 
            " AND tbf.IsActive = 1 AND (tbf.FieldType = 'char' OR tbf.ComboSourceManual IS NOT NULL)
            AND IFNULL(tbc.IsComboAutoComplete,FALSE) = FALSE ".$criteriaCompany;
        elseif ($type == 'list') $criteria = " AND tbf.FieldType = 'char' ".$criteriaCompany;
        elseif ($type == 'report') $criteria = " AND tbf.FieldType = 'char' AND IsReportGenerator=true ".$criteriaCompany;
        elseif ($type == 'all') $criteria = " AND (tbf.FieldType = 'char' OR tbf.ComboSourceManual IS NOT NULL) ".$criteriaCompany;
        elseif ($type == 'detail') {
            $tableDetails = $this->getTableDetails($tableData, false);
            $criteria = " AND tbf.FieldType = 'char' AND (tbp.Code = '{$table}' OR tbp.Name = '{$table}' OR tb.Code IN ({$tableDetails}))";
        }

        $query = "SELECT tbf.Oid, tbf.Code AS FieldName, tbc.Code AS TableName, 
            tb.Name AS TableParentCode, tb.APITableParentRelationshipName AS TableParentDisplayName,
            tbc.Code AS TableComboCode, tbc.Name AS TableComboCode, tbc.Combo1 AS TableComboField,
            tbf.ComboSourceManual, tbc.Combo1 AS TableFieldDisplay, tbc.FormType AS TableFormType
            FROM apitablefield tbf 
            LEFT OUTER JOIN apitable tbc ON tbf.APITableCombo = tbc.Oid
            LEFT OUTER JOIN apitable tb ON tbf.APITable = tb.Oid
            LEFT OUTER JOIN apitable tbp ON tb.APITableParent = tbp.Oid
            WHERE tbf.GCRecord IS NULL 
            AND tbf.Code NOT IN ('Oid','id','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy')
            {$criteria}";
        return $this->dbConnection->select(DB::raw($query));
    }

    public function functionGetFieldsImageFromTable($table) {
        $query = "SELECT tbf.Oid, tbf.Code AS FieldName, tb.Name AS TableParentCode
            FROM apitablefield tbf 
            LEFT OUTER JOIN apitable tb ON tbf.APITable = tb.Oid
            WHERE tbf.GCRecord IS NULL AND tbf.IsImage = true AND tbf.IsActive = true
            AND tbf.Code NOT IN ('Oid','id','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy')
            AND (LOWER(tb.Code) = '{strtolower($table)}' OR LOWER(tb.Name) = '{strtolower($table})')";
        return $this->dbConnection->select(DB::raw($query));
    }

    public function getTableDetails($tabledata, $returnAll = true) {
        
        $tmpDetails = "null";
        if ($tabledata->IsUsingModuleApproval) $tmpDetails = $tmpDetails.($tmpDetails ? "," : "")."'pubapproval'";
        if ($tabledata->IsUsingModuleImage) $tmpDetails = $tmpDetails.($tmpDetails ? "," : "")."'mstimage'";
        if ($tabledata->IsUsingModuleComment) $tmpDetails = $tmpDetails.($tmpDetails ? "," : "")."'pubcomment'";
        if ($tabledata->IsUsingModuleFile) $tmpDetails = $tmpDetails.($tmpDetails ? "," : "")."'pubfile'";
        if ($tabledata->IsUsingModuleEmail) $tmpDetails = $tmpDetails.($tmpDetails ? "," : "")."'mstemail'";
        if ($tabledata->TableDetails) {
            $tableDetails = json_decode($tabledata->TableDetails);
            foreach($tableDetails as $tableDetail) $tmpDetails = $tmpDetails.($tmpDetails ? "," : "")."'".$tableDetail."'";
        }
        $query = "SELECT tb.*, tbp.Name AS FieldParent,        
            CASE WHEN tfs.Oid IS NULL THEN FALSE ELSE TRUE END AS IsFieldSequence,
            CASE WHEN tfd.Oid IS NULL THEN FALSE ELSE TRUE END AS IsFieldDate
            FROM apitable tb 
            LEFT OUTER JOIN apitable tbp ON tbp.Oid = tb.APITableParent
            LEFT OUTER JOIN apitablefield tfs ON tfs.APITable = tb.Oid AND tfs.Code = 'Sequence'
            LEFT OUTER JOIN apitablefield tfd ON tfd.APITable = tb.Oid AND tfd.Code = 'Date'
            WHERE (tb.APITableParent='{$tabledata->Oid}' OR tb.Code IN ({$tmpDetails}))
            AND tb.APITableParentRelationshipName IS NOT NULL";
        if ($returnAll) return $this->dbConnection->select(DB::raw($query));
        else return $tmpDetails;
    }

    public function getTableConstraint($tabledata) {        
        $query = "SELECT tb.Code TableCode, tb.Name TableName, tbf.*
            FROM apitablefield tbf 
            LEFT OUTER JOIN apitable tb ON tb.Oid = tbf.APITable
            WHERE tb.Code NOT IN ('pubpost','pubfile','pubimage','pubapproval') 
            AND tbf.APITableCombo = '{$tabledata->Oid}' 
            AND tb.APITableParent != '{$tabledata->Oid}'";
        return $this->dbConnection->select(DB::raw($query));
    }

    public function getDataModule($where) {
        try {
            $query = "SELECT * FROM sysmodule WHERE GCRecord IS NULL ".$where;
            $table = $this->dbConnection->select(DB::raw($query));
            return $table;
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function getDataJSON($code, $type) {
        try {
            $code = strtolower($code);
            $query = "SELECT tbp.Name AS APITableParentFieldName, tb.*,
                CASE WHEN tfc.Oid IS NULL THEN FALSE ELSE TRUE END AS IsFieldCode,
                CASE WHEN tfs.Oid IS NULL THEN FALSE ELSE TRUE END AS IsFieldSequence,
                CASE WHEN tfd.Oid IS NULL THEN FALSE ELSE TRUE END AS IsFieldDate,
                CASE WHEN tfn.Oid IS NULL THEN FALSE ELSE TRUE END AS IsFieldName
                FROM apitable tb 
                LEFT OUTER JOIN apitable tbp ON tbp.Oid = tb.APITableParent
                LEFT OUTER JOIN apitablefield tfc ON tfc.APITable = tb.Oid AND tfc.Code = 'Code'
                LEFT OUTER JOIN apitablefield tfs ON tfs.APITable = tb.Oid AND tfs.Code = 'Sequence'
                LEFT OUTER JOIN apitablefield tfd ON tfd.APITable = tb.Oid AND tfd.Code = 'Date'
                LEFT OUTER JOIN apitablefield tfn ON tfn.APITable = tb.Oid AND tfn.Code = 'Name'
                WHERE (LCASE(tb.Name)='{$code}' OR LCASE(tb.Code)='{$code}' OR LCASE(tb.Oid) = '{$code}')";
            $table = $this->dbConnection->select(DB::raw($query));
            if ($table) $table = $table[0];
            
            if (gettype($table) == 'array') $table = json_decode(json_encode($table), FALSE);
            
            if ($type == 'presearch') return isset($table->PreSearch) ? json_decode($table->PreSearch) : null;
            if ($type == 'topbutton') return isset($table->Multibutton) ? json_decode($table->Multibutton) : null;
            if ($type == 'action') return isset($table->ActionDropDownRow) ? json_decode($table->ActionDropDownRow) : null;
            if ($type == 'all') return $table;
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function functionGetDefaultSort($code) {
        try {
            $query = "SELECT tbf.*
                FROM apitablefield tbf
                LEFT OUTER JOIN apitable tb ON tb.Oid = tbf.APITable
                WHERE (tb.Name='{$code}' OR tb.Code='{$code}')
                AND tbf.IsActive = true AND tbf.GCRecord IS NULL AND tbf.Code = 'Name'
                ORDER BY Sequence";
            $data = $this->dbConnection->select(DB::raw($query));
            return $data ? 'Name' : 'CreatedAt';
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function generateVueList($code, $listShow = true) {
        $tableData = $this->getDataJSON($code, 'all');
        $customField = $this->getCustomFieldSetting($tableData->Oid,$tableData->Code,$tableData->Name);
        
        if ($listShow) $listShow = "AND tbf.IsListShowPrimary = true";
        else $listShow = "";
        $where = "AND (tb.Name='{$code}' OR tb.Code='{$code}') AND tbf.IsActive=true ".$listShow.
            (isset($customField->Hide) ? $customField->Hide : null);

        $data = $this->generateVueListSub($where);
        return $this->subGenerate($data, 'ListAndForm_Master',$customField);
    }

    public function generateVueListSub($where = null, $logger = false) {
        $query = "SELECT tbf.*, 
                tbc.IsComboAutoComplete APITableComboObj_IsAutoComplete,
                tbc.Combo1 APITableComboObj_Combo1,
                tbc.ComboStoreSource APITableComboObj_ComboStoreSource,
                tb.APITableParent APITableObj_APITableParent,
                tbp.Name APITableObj_APITableParentObj_Name
            FROM apitablefield tbf
            LEFT OUTER JOIN apitable tb ON tb.Oid = tbf.APITable
            LEFT OUTER JOIN apitable tbp ON tbp.Oid = tb.APITableParent
            LEFT OUTER JOIN apitable tbc ON tbc.Oid = tbf.APITableCombo
            WHERE tbf.GCRecord IS NULL {$where}
            ORDER BY IFNULL(tb.SequenceAddition,0)+tbf.Sequence";
        if ($logger) dd($query);
        return $this->dbConnection->select(DB::raw($query));
    }
    
    public function generateVueMaster(Request $request) {
        $tableData = $this->getDataJSON($request->query('code'), 'all');
        $data = $this->generateVueList($tableData->Code, $tableData->FormType == 'Transaction');
        $customField = $this->getCustomFieldSetting($tableData->Oid,$tableData->Code,$tableData->Name);
        return $this->subGenerate($data, 'ListAndForm_Master',$customField);
    }
    
    public function generateVuePopup($table, $fields) {
        $tableData = $this->getDataJSON($table, 'all');
        $result = null;
        foreach($fields as $f) $result = $result.($result == null ? "" : ",")."'".$f."'";
        $data = $this->generateVueListSub("AND tb.Code ='".$tableData->Code."' AND tbf.Code IN (".$result.")");
        $customField = $this->getCustomFieldSetting($tableData->Oid,$tableData->Code,$tableData->Name);
        return $this->subGenerate($data, 'FormPopupInput',$customField);
    }

    private function getCustomFieldSetting($oid,$code,$name) {
        $company = Auth::user()->CompanyObj;
        if ($company->CustomFieldSetting) {
            $companyFieldHide = null;
            $companyFieldHideNot = null;
            $companyFieldDisabled = null;
            $companyLabel = null;
            $companyTab = null;

            $data = json_decode($company->CustomFieldSetting);
            foreach($data as $row) {
                if (!isset($row->type)) continue;
                if (in_array($row->table, [$oid,$code,$name])) {
                    if ($row->type == 'field') {
                        if (isset($row->hide)) {
                            if ($row->hide) $companyFieldHide = $companyFieldHide.($companyFieldHide ? ",'" : "'").$row->code."'";
                            else $companyFieldHideNot[] = $row;
                        }
                        if (isset($row->disabled)) $companyFieldDisabled[] = $row->code;
                        if (isset($row->title)) $companyLabel[] = $row;
                    } elseif ($row->type == 'tab') {
                        $companyTab[] = $row;
                    }
                }
            }

            if ($companyFieldHide) $companyFieldHide = " AND tbf.Code NOT IN (".$companyFieldHide.") ";           
            // if ($companyFieldDisabled) dd($companyFieldDisabled);
            // if ($companyLabel) dd($companyLabel);
            return (object) [
                "Hide" => $companyFieldHide,
                "HideNot" => $companyFieldHideNot,
                "Disabled" => $companyFieldDisabled,
                "Title" => $companyLabel,
                "Tab" => $companyTab,
            ];
        } else {
            return (object) [
                "Hide" => null,
                "HideNot" => null,
                "Disabled" => null,
                "Title" => null,
                "Tab" => null,
            ];
        }
    }

    public function generateVueTransaction(Request $request) {
        
        $query = "SELECT * FROM sysmodule WHERE LCASE(Code)='".strtolower($request->query('code'))."' OR LCASE(Name)='".strtolower($request->query('code'))."'";
        $table = $this->dbConnection->select(DB::raw($query));
        if ($table) $table = $table[0];
        
        $isReport = isset($table) ? isset($table->ReportAPI) : null;        
        if ($isReport) {
            $query = "SELECT * FROM apireport WHERE ModuleReport='{$table->Code}'";
            $reports = $this->dbConnection->select(DB::raw($query));
            
            if ($reports && $table->IsReportGenerator) {
                $options = [];
                foreach ($reports as $row) {
                    $options[] = [
                        'Oid'=> $row->Code,
                        'Name'=> $row->Name,
                    ];
                }
                $reportApi = 'report/generate';
            } else {
                $options = json_decode($table->ReportOptions);
                $reportApi = $table->ReportAPI;
            }
            $criterias = json_decode($table->ReportCriterias);
            $firstDayOfMonth = date("Y-m-01", strtotime(now()));
            $lastDayOfMonth = date("Y-m-t", strtotime(now()));
            foreach($criterias as $row) {
                if (!isset($row->default)) continue;
                if ($row->default == 'firstday') $row->default = $firstDayOfMonth;
                if ($row->default == 'lastday') $row->default = $lastDayOfMonth;
            }
            return [
                "main" => [
                    "name" => "Main",
                    "icon" => "SettingsIcon",
                    "editButton" => "false",
                    "hideWhen" => null,
                    "multiButton" => [
                        [                         
                            "name" => "Preview Report",
                            "type" => "open_report",
                            "icon" => "PrinterIcon",
                            "get" => $reportApi."?action=preview",
                            "params" => []
                        ], [                         
                            "name" => "Download Report",
                            "type" => "download_report",
                            "icon" => "PrinterIcon",
                            "get" => $reportApi."?action=download",
                            "params" => []
                        ]
                    ],
                    "fieldGroups"=> [
                        [
                            "name" => "Report Name",
                            "icon" => "SettingsIcon",
                            "editButton" => "false",
                            "hideWhen" => null,
                            "fields"=> [
                                [
                                    "fieldToSave" => "report",
                                    "overideLabel" => "Report Name",
                                    "type" => "combobox", //inputradio
                                    "validationParams" => "required",
                                    "source" => $options
                                ],
                            ]
                        ],[
                            "name" => "Criterias",
                            "icon" => "SettingsIcon",
                            "editButton" => "false",
                            "hideWhen" => null,
                            "fields"=> $criterias
                        ]
                    ]
                ]
            ];
        }

        $return = [];
        
        //NON TAB - NON GROUP
        $fieldsMainPage = [];
        $apiTable = $this->getDataJSON($request->query('code'), 'all');
        $Oid = $apiTable->Oid;

        //CUSTOM FIELD
        $customField = $this->getCustomFieldSetting($apiTable->Oid,$apiTable->Code,$apiTable->Name);

        //onetoone
        $apiTable2 = null;
        $criteriaTable = " AND APITable='".$Oid."' ";
        if (isset($apiTable->APITableOneToOne)) {
            $query = "SELECT * FROM apitable WHERE Oid='{$apiTable->APITableOneToOne}'";  
            $apiTable2 = $this->dbConnection->select(DB::raw($query))[0];
            $criteriaTable = " AND (tbf.APITable='".$Oid."' OR tbf.APITable='".$apiTable2->Oid."')";
        }
        
        $criteria = " AND tbf.IsActive = true AND tbf.LayoutGroup IS NULL AND tbf.LayoutTab IS NULL ".$customField->Hide.$criteriaTable;
        $data = $this->generateVueListSub($criteria);
        if ($data) {
            $fieldsMainPage = $this->subGenerate($data, 'FormTabAndGroup_Transaction', $customField);
            if (count($fieldsMainPage) > 0) $return = array_merge($return, ['main' => $fieldsMainPage]);
        }
        
        //NON TAB - GROUP
        $fieldsGroup = [];
        $query = "SELECT d.* FROM (
            SELECT MIN(IFNULL(tb.SequenceAddition,0)+tbf.Sequence) AS Sequence, tbf.LayoutGroup 
            FROM apitablefield tbf 
            LEFT OUTER JOIN apitable tb ON tb.Oid = tbf.APITable
            WHERE tbf.LayoutTab IS NULL {$criteriaTable}
            AND tbf.LayoutGroup IS NOT NULL AND IFNULL(IsHideInput,FALSE) != TRUE
            GROUP BY LayoutGroup
            ) AS d ORDER BY d.Sequence";
        $groups = $this->dbConnection->select(DB::raw($query));
        foreach($groups as $group) {
            $criteria = "AND tbf.LayoutTab IS NULL AND tbf.LayoutGroup='{$group->LayoutGroup}' 
                AND tbf.IsActive = true ".$customField->Hide.$criteriaTable;
            $data = $this->generateVueListSub($criteria);
            if ($data) {
                $tmp = $this->subGenerate($data, 'FormTabAndGroup_Transaction',$customField);
                if (count($tmp) > 0) $fieldsGroup[] = [
                    'name' => $group->LayoutGroup,
                    'icon' => 'SettingsIcon',
                    'fields' => $tmp,
                ];
            }
        }
        if (count($fieldsGroup) > 0) $return = array_merge($return, ['fieldGroups' => $fieldsGroup]);

        //TAB (INSIDE)
        $fieldTabs = [];
        $query = "SELECT d.* FROM (
            SELECT MIN(IFNULL(tb.SequenceAddition,0)+tbf.Sequence) AS Sequence, tbf.LayoutTab 
            FROM apitablefield tbf 
            LEFT OUTER JOIN apitable tb ON tb.Oid = tbf.APITable
            WHERE tbf.LayoutTab IS NOT NULL AND IFNULL(IsHideInput,FALSE) != TRUE {$criteriaTable}
            GROUP BY LayoutTab
            ) AS d ORDER BY d.Sequence;";
        $tabs = $this->dbConnection->select(DB::raw($query));
        foreach($tabs as $tab){
            //TAB - NON GROUP
            $fieldsNonGroup = [];
            $criteria = "AND tbf.LayoutGroup IS NULL AND tbf.LayoutTab='{$tab->LayoutTab}' 
                AND tbf.IsActive = true".$customField->Hide.$criteriaTable;
            $data = $this->generateVueListSub($criteria);
            if ($data) {
                $tmp = $this->subGenerate($data, 'FormTabAndGroup_Transaction',$customField);
                if (count($tmp) > 0) $fieldsNonGroup = $tmp;
            }

            //TAB - GROUP
            $fieldsGroup = [];
            $query = "SELECT d.* FROM (
                SELECT MIN(IFNULL(tb.SequenceAddition,0)+tbf.Sequence) AS Sequence, tbf.LayoutGroup 
                FROM apitablefield tbf
                LEFT OUTER JOIN apitable tb ON tb.Oid = tbf.APITable
                WHERE tbf.LayoutTab='{$tab->LayoutTab}' {$criteriaTable}
                AND tbf.LayoutGroup IS NOT NULL AND IFNULL(IsHideInput,FALSE) != TRUE
                GROUP BY tbf.LayoutGroup
                ) AS d ORDER BY d.Sequence;";
            $groups = $this->dbConnection->select(DB::raw($query));
            foreach($groups as $group) {
                $criteria = "AND tbf.LayoutGroup='{$group->LayoutGroup}' AND tbf.LayoutTab='{$tab->LayoutTab}'
                    AND tbf.IsActive = true ".$customField->Hide.$criteriaTable;
                $data = $this->generateVueListSub($criteria);

                $criteria = "AND tbf.LayoutGroup='{$group->LayoutGroup}' AND tbf.LayoutTab='{$tab->LayoutTab}' 
                    AND tbf.IsActive = true AND tbf.OnHideWhen IS NULL
                    ".$customField->Hide.$criteriaTable;
                $noHidden = $this->generateVueListSub($criteria);

                $hideWhen = null;
                $hideWhenIsSame = true;
                if (count($noHidden) == 0) {
                    $criteria = "AND tbf.LayoutGroup='{$group->LayoutGroup}' AND tbf.LayoutTab='{$tab->LayoutTab}'
                        AND tbf.IsActive = true AND tbf.OnHideWhen IS NOT NULL
                        ".$customField->Hide.$criteriaTable;
                    $noHidden = $this->generateVueListSub($criteria);
                    foreach($noHidden as $row) {
                        if ($hideWhenIsSame) {
                            // logger($tab->LayoutTab.' '.$group->LayoutGroup.' '.$row->Code.' '.$row->OnHideWhen);
                            if (!$hideWhen) $hideWhen = $row->OnHideWhen;
                            elseif ($hideWhen == $row->OnHideWhen) continue;
                            else {
                                $hideWhen = null;
                                $hideWhenIsSame = false;
                            }
                        }   
                    }
                }
                if ($data) {
                    $tmp = $this->subGenerate($data, 'FormTabAndGroup_Transaction',$customField);
                    if ($tab->LayoutTab == 'Main' && $apiTable->IsTabMainSingleColumn) $tmpCol = 1;
                    else $tmpCol = 2;

                    //sum total
                    // $arrTotal = [];
                    // foreach ($data as $f) {
                    //     $arrTotal = [
                    //         'Type' => 'Sum',
                    //         'Field' => 'Price',
                    //         'ColSpan' => 3,
                    //     ];
                    //     break;
                    // }
                    // $return['fieldTabs'][2]['totalFooter'] = [
                    //     'Type'=>'Sum', 
                    //     "Field"=> 'Price',
                    //     "ColSpan"=>3
                    // ];

                    if (count($tmp) > 0) $fieldsGroup[] = [
                        'name' => $group->LayoutGroup,
                        'column' => '1/'.$tmpCol,
                        'icon' => 'SettingsIcon',
                        'hideWhen' => $hideWhen ? (isJson($hideWhen) ? json_decode($hideWhen) : $hideWhen ) : null,
                        'fields' => $tmp,
                    ];
                }
            }

            if ($fieldsNonGroup == [] && $fieldsGroup == []) continue;            
            $skip = false;
            if ($customField->Tab) foreach($customField->Tab as $row) if ($tab->LayoutTab == $row->code && isset($row->hide)) $skip = $row->hide;
            if (!$skip) {
                $hideWhen = null;
                if (isset($apiTable->CustomTabHideWhen)) {
                    $tmp = json_decode($apiTable->CustomTabHideWhen);
                    foreach($tmp as $r) {
                        if (isset($r->type) && isset($r->hideWhen)) {
                            if ($r->type == 'tab' && $r->name == $tab->LayoutTab) $hideWhen = $r->hideWhen;
                        }
                    }
                }

                $arr = [
                    'name' => $tab->LayoutTab,
                    "addButton" => $apiTable->IsDisabledCreate ? false : true,
                    'editButton' => true,
                    'icon' => 'SettingsIcon',
                ];
                if ($tab->LayoutTab == "Upload Setting") $arr = array_merge($arr, ['hideWhen' => ['add', '<>travel']] );
                elseif ($tab->LayoutTab != 'Main' && !$hideWhen) $arr = array_merge($arr, ['hideWhen' => 'add'] );
                elseif ($hideWhen) $arr = array_merge($arr, ['hideWhen' => $hideWhen] );
                
                if (count($fieldsNonGroup) > 0) $arr = array_merge($arr, ['fields' => $fieldsNonGroup]);
                if (count($fieldsGroup) > 0) $arr = array_merge($arr, ['fieldGroups' => $fieldsGroup]);
                $fieldTabs[] = $arr;
            }
        }

        $tmpDetails = "null";
        if ($apiTable->IsUsingModuleApproval) $tmpDetails = $tmpDetails.($tmpDetails ? "," : "")."'pubapproval'";
        if ($apiTable->TableDetails) {
            $tableDetails = json_decode($apiTable->TableDetails);
            foreach($tableDetails as $tableDetail) $tmpDetails = $tmpDetails.($tmpDetails ? "," : "")."'".$tableDetail."'";
        }
        
        $query = "SELECT tb.Oid, tb.Code, tb.Name, tb.APITableParentRelationshipName, tb.Title, tb.FormType, tb.EditBatchFieldGroup,
            COUNT(tbf.Oid) AS Counta, tb.IsDisabledCreate, tb.IsDisabledEdit, 
            tb.MultiButton, tb.APISaveNotBatch, tb.APIDeleteNotBatch, tb.OnHideWhen, tb.ActionDropDownRow
            FROM apitable tb LEFT OUTER JOIN apitablefield tbf ON tb.Oid = tbf.APITable
            WHERE (tb.APITableParent = '{$Oid}' OR tb.Code IN (".$tmpDetails.")) AND tb.IsActive = true AND tb.Name IS NOT NULL
            GROUP BY tb.Oid, tb.Code, tb.Name, tb.APITableParentRelationshipName ORDER BY Counta DESC";
        $dataDetails = $this->dbConnection->select(DB::raw($query));
        foreach($dataDetails as $dataDetail) {
            //CUSTOM FIELD
            $customFieldDtl = $this->getCustomFieldSetting($dataDetail->Oid,$dataDetail->Code,$dataDetail->Name);

            $criteria = "AND tbf.IsActive = TRUE AND tbf.APITable = '{$dataDetail->Oid}' AND tbf.Code != 'Company' ".$customFieldDtl->Hide;
            $data = $this->generateVueListSub($criteria);

            $hide = false;
            if ($customField->Tab) {
                foreach ($customField->Tab as $row) {
                    if ($dataDetail->APITableParentRelationshipName == $row->code && isset($row->hide)) {
                        $hide = $row->hide == true ? true : false;
                        logger($dataDetail->APITableParentRelationshipName.' '.$row->code.' '.$hide);
                    }
                }
            }
            if ($data) {
                $arr = [
                    'name' => $dataDetail->APITableParentRelationshipName, //$dataDetail->Title ?: 
                    'overrideLabel' => $dataDetail->Title ? $dataDetail->Title : $dataDetail->APITableParentRelationshipName,
                    'fieldToSave' => $dataDetail->APITableParentRelationshipName,
                    'type' => $dataDetail->FormType == 'DetailBatch' ? 'tablebatch' : 'table',
                    'addButton' => !$dataDetail->IsDisabledCreate,
                    'showModal' => false,
                    'store' => $dataDetail->APISaveNotBatch ?: '',
                    'delete' => $dataDetail->APIDeleteNotBatch ?: '',
                    'params' => 'id',
                    "tableData" => [],
                ];
                if (isset($dataDetail->OnHideWhen)) if (isJson($dataDetail->OnHideWhen)) 
                    $arr = array_merge($arr, [ 'hideWhen' => [ "add", json_decode($dataDetail->OnHideWhen) ] ]);
                else $arr = array_merge($arr,[ 'hideWhen' => "add" ]);
                if (isset($dataDetail->MultiButton)) {
                    $actions = json_decode($dataDetail->MultiButton);
                    $actions = actionCheckCompany($dataDetail->Code, $actions);
                    if ($actions) $arr = array_merge($arr, [ 'MultiButton' => $actions ]);
                }
                $tmp = $this->subGenerate($data, 'FormPopupInput',$customFieldDtl);
                if (count($tmp) > 0) $arr = array_merge($arr,[ 'form' => $tmp ]);
            }

            $criteria = "AND tbf.IsListShowPrimary = TRUE AND tbf.APITable = '{$dataDetail->Oid}' AND tbf.Code != 'Company'".$customFieldDtl->Hide;
            $data = $this->generateVueListSub($criteria);
            if ($data) {
                $action = ['edit','delete'];
                $criteria = "AND tbf.IsListShowPrimary = TRUE AND tbf.IsInlineEdit = TRUE AND tbf.APITable = '{$dataDetail->Oid}'".$customFieldDtl->Hide;
                $check = $this->generateVueListSub($criteria);
                if (count($check) >0) $action = array_merge($action, ['inlineEdit']);
                if ($dataDetail->ActionDropDownRow) $action = array_merge($action, json_decode($dataDetail->ActionDropDownRow));
                
                if ($dataDetail->FormType == 'DetailBatch') {
                    $tmp = $this->subGenerate($data, 'List_InlineEdt',$customFieldDtl);
                    if ($dataDetail->EditBatchFieldGroup) {
                        $i = 0;
                        foreach ($tmp as $r) {
                            if ($r['field'] != 'Action') {
                                $tmp[$i] = array_merge($tmp[$i], [ 'group'=>true ]);
                                break;
                            }
                            $i = $i+1;                            
                        }
                    }                    
                    $action = ['delete'];
                } else $tmp = $this->subGenerate($data, 'ListOnly',$customFieldDtl);
                if (count($tmp) > 0) {
                    if (!$dataDetail->IsDisabledEdit) {
                        $tmp[] = [
                            'field' => 'Action',
                            'type' => 'endAction',
                            'action' => $action,
                        ];
                    }
                    $arr = array_merge($arr,[ 'list' => $tmp ]);
                }                
            }

            
            $hide = false;
            if ($customField->Tab) {
                foreach ($customField->Tab as $row) {
                    if ($dataDetail->APITableParentRelationshipName == $row->code && isset($row->hide)) {
                        $hide = $row->hide == true ? true : false;                        
                        // logger($dataDetail->APITableParentRelationshipName.' '.$row->code.' '.$hide);
                    }
                }
            }
            if ($arr && !$hide) $fieldTabs[] = $arr;
        } 

        if (isset($apiTable->AdditionalTab)) {
            $tmp = json_decode($apiTable->AdditionalTab);
            foreach($tmp as $row) $fieldTabs[] = $row;
        }

        if ($apiTable->IsUsingModuleComment) {
            $arr = [
                'name' => 'Comments',
                'type' => 'comment',
                'hideWhen' => 'add',
                'icon' => 'CommentIcon',
                'form' => null,
                'list' => null,
                'post' => 'publiccomment/create?Oid={Oid}&Type='.$apiTable->Name,
                'delete' => 'publiccomment/{Oid}'
            ];
            $fieldTabs[] = $arr;
        }

        if ($apiTable->IsUsingModuleImage) {
            $arr = [
                'name' => 'Images',
                'type' => 'image',
                'hideWhen' => 'add',
                'icon' => 'ImageIcon',
                'form' => null,
                'list' => null,
                'post' => 'image?Oid={Oid}&Type='.$apiTable->Name,
                'delete' => 'image/{Oid}'
            ];
            $fieldTabs[] = $arr;
        } 

        if ($apiTable->IsUsingModuleFile) {
            $arr = [
                'name' => 'Files',
                'type' => 'file',
                'hideWhen' => 'add',
                'icon' => 'FilePlusIcon',
                'form' => null,
                'list' => null,
                'post' => 'file/upload?Oid={Oid}&Type='.$apiTable->Name,
                'delete' => 'file/{Oid}'
            ];
            $fieldTabs[] = $arr;
        }
        
        if ($apiTable->IsUsingModuleEmail) {
            // $apiEmail = isset($apiTable->APIModuleEmail) ? $apiTable->APIModuleEmail
            $arr = [
                'name' => 'Emails',
                'type' => 'email',
                'hideWhen' => 'add',
                'icon' => 'FilePlusIcon',
                'form' => null,
                'list' => null,
                'post' => 'sendemail?'.strtolower($apiTable->Name).'={Oid}',
                'delete' => 'email/{Oid}'
            ];
            $fieldTabs[] = $arr;
        }        

        $fieldTabs[] = [
            'name' => 'Information',
            "icon" => "SettingsIcon",
            "editButton" => "false",
            "hideWhen" => null,
            "disabled"=> true,
            'fieldGroups' => [
                    [
                        "name" => "Main",
                        "column" => "1/1",
                        "icon" => "InformationIcon",
                        "fields" => [
                            [
                                "fieldToSave" => "CreatedByName",
                                "type" => "inputtext",
                                "overrideLabel" => "Created By",
                                "disabled" => true,
                                "column" => "1/2"
                            ],
                            [
                                "fieldToSave" => "CreatedAt",
                                "type" => "inputtext",
                                "overrideLabel" => "Created At",
                                "disabled" => true,
                                "column" => "1/2"
                            ],
                            [
                                "fieldToSave" => "UpdatedByName",
                                "type" => "inputtext",
                                "overrideLabel" => "Updated By",
                                "disabled" => true,
                                "column" => "1/2"
                            ],
                            [
                                "fieldToSave" => "UpdatedAt",
                                "type" => "inputtext",
                                "overrideLabel" => "Updated At",
                                "disabled" => true,
                                "column" => "1/2"
                            ],
                    ]
                ]
            ]
        ];

        if (count($fieldTabs) > 0) $return = array_merge($return, ['fieldTabs' => $fieldTabs]);
        if ($apiTable->Name == 'PurchaseInvoice') {
            $return['fieldTabs'][2]['totalFooter'] = [
                'Type'=>'Sum', 
                "Field"=> 'Price',
                "ColSpan"=>3
            ];
            // $return['fieldTabs'][2]['list'][1]['total'] = 'count';
            // $return['fieldTabs'][2]['list'][2]['total'] = 'sum';
            // $return['fieldTabs'][2]['list'][3]['total'] = 'sum';
            // // $return['fieldTabs'][1]['list'][1] = array_merge($return['fieldTabs'][1]['list'][2],$return['fieldTabs'][2]['list'][2]['editType'][0]);
            // $return['fieldTabs'][1]['list'][] = $return['fieldTabs'][1]['list'][5];
            // $return['fieldTabs'][1]['list'][5] = [
            //     'field'=>'IsActive',
            //     'name'=>'IsActive',
            //     'width'=>100,
            //     "fieldToSave" => "IsActive",
            //     "type" => "checkbox"
            // ];
        };
        return $return;
    }

    private function subGenerate($data,$generateType,$customField) {    
        $arr = [];
        $fields = [];
        if ($generateType == 'ListAndForm_Master') $fields[] = [
            'headerName' => '',
            'field' => 'Action',
            'width' => 50,
            'resizable' => false,
            'cellRenderer' => 'actionCell',
            'topButton' => [
                [
                    'name' => 'Add New',
                    'icon' => 'PlusIcon',
                    'type' => 'add'
                ]
            ]
        ];        
        if ($generateType == 'List_InlineEdt') $fields = [];
        foreach($data as $row) {
            if (!isset($row->Code)) continue;
            if ($generateType == 'ListOnly' && !$row->IsListShowPrimary) continue;
            if (isset($row->APITableObj_APITableParent)) if ($row->Code == $row->APITableObj_APITableParentObj_Name) continue;
            $arr = $this->subGenerateField($row, $generateType,$customField);
            if ($arr != []) $fields[] = $arr;
        }
        return $fields;
    }

    private function subGenerateField($row,$generateType,$customField) {
        $disabled = ['Oid','GCRecord','OptimisticLock','OptimisticLockField','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];            
        if ($generateType != 'ListOnly') $disabled = array_merge($disabled,['CreatedAt']);
        if (in_array($row->Code, $disabled)) return null;

        if ($row->ComboSourceManual) {

            $result = (object) [
                'type' => 'combobox',
                'validationParams' => $row->IsRequired ? "required" : null,
                'default' => $row->DefaultValue ? $row->DefaultValue : null,
                'field' => $row->Code,
                'fieldToSave' => $row->Code,
                'fieldToSearch' => "data.".$row->Code,
                'source' => json_decode($row->ComboSourceManual),
                'hideInput'=>$row->IsHideInput ? $row->IsHideInput : false,
            ];

        } elseif (in_array($row->FieldType, ['char'])) {

            $result = (object) [
                'type' => (isset($row->APITableComboObj_IsAutoComplete) ? $row->APITableComboObj_IsAutoComplete : false) ? 'autocomplete' : 'combobox',
                'validationParams' => $row->IsRequired ? "required" : null,
                'default' => $row->DefaultValue ? $row->DefaultValue : null,
                'field' => $row->Code.'Name',
                'fieldToSave' => $row->Code,
                'fieldToSearch' => $row->Code.".".($row->APITableComboObj_Combo1 ? $row->APITableComboObj_Combo1 : 'Name'),
                'source' => isset($row->APITableComboObj_ComboStoreSource) ? strtolower($row->APITableComboObj_ComboStoreSource) : null,
                'hideInput'=>$row->IsHideInput ? $row->IsHideInput : false,
            ];

        } elseif (in_array($row->FieldType, ['date','datetime'])) {

            $result = (object) [
                'type' => 'inputdate',
                'validationParams' => $row->IsRequired ? "required" : null,
                'default' => $row->DefaultValue ? now()->format('Y-m-d') : null,
                'field' => $row->Code,
                'fieldToSave' => $row->Code,
                'fieldToSearch' => "data.".$row->Code,
                'hideInput'=>$row->IsHideInput ? $row->IsHideInput : false,
            ];

        } elseif (in_array($row->FieldType, ['bool', 'bit'])) {

            $result = (object) [
                'type' => 'checkbox',
                'validationParams' => $row->IsRequired ? "required" : null,
                'default' => $row->DefaultValue == "true" ? true : false,
                'field' => $row->Code,
                'fieldToSave' => $row->Code,
                'fieldToSearch' => "data.".$row->Code,
                'hideInput'=>$row->IsHideInput ? $row->IsHideInput : false,
            ];

        } elseif (in_array($row->FieldType, ['int','smallint','bigint','decimal','double'])) {

            $result = (object) [
                'type' => 'inputtext',
                // 'validationParams' => "regex:/^\d{1,3}(?:\.\d{3})*(?:,\d+)?$/".($row->IsRequired ? "|required" : ""),
                'validationParams' => "money".($row->IsRequired ? "|required" : ""),
                'default' => $row->DefaultValue ? $row->DefaultValue : "",
                'field' => $row->Code,
                'fieldToSave' => $row->Code,
                'fieldToSearch' => "data.".$row->Code,
                'hideInput'=>$row->IsHideInput ? $row->IsHideInput : false,
            ];
            // $result->validationParams = "decimal".($result->required ? "|required" : "");

        } elseif (in_array($row->FieldType, ['text'])) {

            $result = (object) [
                'type' => 'inputarea',
                'validationParams' => null,
                'default' => $row->DefaultValue ? $row->DefaultValue : null,
                'field' => $row->Code,
                'fieldToSave' => $row->Code,
                'fieldToSearch' => "data.".$row->Code,
                'hideInput'=>$row->IsHideInput ? $row->IsHideInput : false,
            ];

        } elseif (in_array($row->FieldType, ['longtext', 'mediumtext'])) {

            $result = (object) [
                'type' => 'inputeditor',
                'validationParams' => null,
                'default' => $row->DefaultValue ? $row->DefaultValue : null,
                'field' => $row->Code,
                'fieldToSave' => $row->Code,
                'fieldToSearch' => "data.".$row->Code,
                'hideInput'=>$row->IsHideInput ? $row->IsHideInput : false,
            ];
            
        } else {

            $result = (object) [
                'type' => 'inputtext',
                'required' => $row->IsRequired,
                'validationParams' => $row->IsRequired ? "required" : null,
                'default' => $row->DefaultValue ? $row->DefaultValue : "",
                'field' => $row->Code,
                'fieldToSave' => $row->Code,
                'fieldToSearch' => "data.".$row->Code,
                'source' => '',
                'hideInput'=>$row->IsHideInput ? $row->IsHideInput : false,
            ];

        }
        
        //FIELD FOR SURE
        $arr =[
            'fieldToSave' => $result->fieldToSave,
            'type' => $result->type,
        ];
        
        //IMAGE
        if (strpos("aaa".$row->Code,'Image')>0 || $row->IsImage) $result->type = 'image';
        //OID
        if ($row->Code == 'Oid') $result->fieldToSave = null;

        //GLOBALSETTING
        if ($row->APITableCombo && $row->IsGlobalSetting) {            
            $tmp = $this->getDataJSON($row->APITableCombo, 'all');
            $result->title = $tmp->GlobalTitle;
            $result->width = $tmp->GlobalWidth;
            $result->islistshow = $row->IsListShowPrimary;
            $result->maxcharacter = $tmp->GlobalMaxCharacter;
            $result->default = $tmp->GlobalDefaultValue;
            $result->disabledwhen = $tmp->GlobalDisabledWhen;
            $result->isdisabled = $tmp->GlobalIsDisabled;
            $result->isrequired = $tmp->GlobalIsRequired;
        } else {       
            $result->title = $row->Name;
            $result->width = $row->Width;
            $result->islistshow = $row->IsListShowPrimary;
            $result->maxcharacter = $row->MaxCharacter;
            $result->default = $result->default;
            $result->disabledwhen = $row->DisabledWhen;
            $result->isdisabled = $row->IsDisabled;
            $result->isrequired = $row->IsRequired;
        }
        if ($customField->Disabled) foreach($customField->Disabled as $f) if ($f == $row->Code) $result->isdisabled = true;
        if ($customField->HideNot) foreach($customField->HideNot as $f) if ($f->code == $row->Code) $result->hideInput = $f->hide;
        if ($customField->Title) foreach($customField->Title as $f) if ($f->code == $row->Code) $result->title = $f->title;

        //HIDE
        $fieldMustShow = ['Oid','Code','Date','Name','Currency','Item','Account','BusinessPartner,','Status','Warehouse','User','IsActive','TotalAmount','Customer','Subtitle','PurchaseBusinessPartnerName','TravelTransportBrand','City','Stock'];
        if ($row->Code == 'Oid') $result->hide = true;
        elseif (isset($result->hideInput)) $result->hide = $result->hideInput;
        elseif (isset($row->IsHideInput)) $result->hide = $row->IsHideInput == 1 ? true : false;
        elseif (!in_array($result->field, $fieldMustShow)) $result->hide = false;
        else $result->hide = false;
        
        switch ($generateType) {
            case 'ListAndForm_Master':             
                $arr =[
                    'headerName' => $result->title,
                    'field' => $result->field,
                    'fieldToSave' => $result->fieldToSave,
                    'fieldToSearch' => $result->fieldToSearch,
                    'overrideLabel' => $result->title,
                    'type' => $result->type,
                    'filter' => 'agTextColumnFilter',
                    'width' => $result->width,
                    // 'headerValueGetter' => 'this.translate',
                    // 'pinned' => 'left',
                    // 'filter' => true,
                ];
                if ($row->IsListHeader) $arr = array_merge($arr, [ 'header' => true, ]);
                if ($result->hide) $arr = array_merge($arr, [ 'hide' => true, ]);
                // if ($result->validationParams) $arr = array_merge($arr, [ 'validationParams' => $result->validationParams, ]);

                $arr = array_merge($arr, [ 'hideInput' => false, ]);
            break;
            case "List_InlineEdt":           
                $arr =[
                    'headerName' => $result->title,
                    'field' => $result->field,
                    'fieldToSave' => $result->fieldToSave,
                    'fieldToSearch' => $result->fieldToSearch,
                    'type' => $result->type,
                    'filter' => 'agTextColumnFilter',
                    'width' => $result->width,
                    'hideLabel' => true,
                    // 'headerValueGetter' => 'this.translate',
                    // 'pinned' => 'left',
                    // 'filter' => true,
                ];
                if ($row->Code == 'Oid') $result->hide = true;
                elseif (!$row->IsListShowPrimary) $result->hide = true;
                elseif (!in_array($result->field, $fieldMustShow)) $result->hide = false;
                else $result->hide = false;
                if ($row->IsListHeader) $arr = array_merge($arr, [ 'header' => true, ]);
                if ($result->hide) $arr = array_merge($arr, [ 'hide' => true, ]);

                $arr = array_merge($arr, [ 'hideInput' => false, ]);
            break;
            case 'ViewOnly': 
                $result->type = 'ViewOnly';
                $arr =[
                    'fieldToSave' => $result->field,
                    'overrideLabel' => $result->title,
                    'type' => $result->type,
                    'initialDisable' => true,
                ]; 
            break;
            case 'FormTabAndGroup_Transaction': 
                $arr =[
                    'fieldToSave' => $result->fieldToSave,
                    'type' => $result->type,
                    'overrideLabel' => $result->title,
                    'initialDisable' => true,
                ]; 
            break;
            case 'FormPopupInput':    
                $arr =[
                    'fieldToSave' => $result->fieldToSave,
                    'overrideLabel' => $result->title,
                    'type' => $result->type,
                ];
            break;
            case 'ListOnly':   
                $arr =[
                    'field' => $result->field,
                    'name' => $row->Name,
                    'overrideLabel' => $result->title,
                    'width' => $result->width,
                ];
                if ($row->IsInlineEdit && $row->FieldType != 'char') $arr = array_merge($arr, [ 
                    'editType' => [[
                    'disabled' => $row->IsDisabledEdit == true ? true : false,
                    "fieldToSave" => $result->fieldToSave,
                    "type" => $result->type
                ]] ]);
                if ($row->IsListHeader) $arr = array_merge($arr, [ 'header' => true, ]);
                // if ($row->Code == 'Oid') $arr = array_merge($arr, [ 'suppressToolPanel' => true, ]);
            break;
        }
        if ($result->validationParams) $arr = array_merge($arr, [ 'validationParams' => $result->validationParams, ]);
        if ($generateType != 'ListOnly') {
            // CUSTOM FIELD
            if (isset($result->source)) if ($result->source == 'currency') $result->default = ["localCompany","CurrencyObj"];
            if ($row->LayoutColumn >= 2) $arr = array_merge($arr, [ 'column' => '1/'.$row->LayoutColumn]);
            // elseif ($row->LayoutColumn == null || $row->LayoutColumn < 1) $arr = array_merge($arr, [ 'column' => null]);

            // if ($row->Code == 'Oid') $result->hideInput = true;
            // elseif ($row->IsHideInput) $result->hideInput = true;
            // else $result->hideInput = false;
            if ($result->hide) $arr = array_merge($arr, [ 'hideInput' => true, ]);       
            
            if ($result->isdisabled) $arr = array_merge($arr, [ 'disabled' => true, ]);            
            if ($result->default && $generateType != 'ViewOnly') {
                if (isJson($result->default)) {
                    $arr = array_merge($arr, [ 'default' => json_decode($result->default), ]);
                } else {
                    $arr = array_merge($arr, [ 'default' => $result->default, ]);
                }                
            } else $arr = array_merge($arr, [ 'default' => null, ]);
            if ($row->Code != $row->Name) $arr = array_merge($arr, [ 'overrideLabel' => $row->Name, ]);
            if ($row->OnHideWhen && $generateType != 'ViewOnly') $arr = array_merge($arr, [ 'hideWhen' => isJson($row->OnHideWhen) ? json_decode($row->OnHideWhen) : $row->OnHideWhen ]);
            if ($row->LayoutNextSeperator) $arr = array_merge($arr, [ 'nextSeperator' => $row->LayoutNextSeperator ]);
            if ($row->OnChange) $arr = array_merge($arr, [ 'onChange' => json_decode($row->OnChange) ]);
            if ($row->DisabledWhen) $arr = array_merge($arr, [ 'disabledWhen' => isJson($row->DisabledWhen) ? json_decode($row->DisabledWhen) : $row->DisabledWhen ]);
            if ($row->OnChangeCalculated) $arr = array_merge($arr, [ 'onInput' => json_decode($row->OnChangeCalculated) ]);
        }
        
        if ($row->ComboSourceManual) {
            $arrcombo = [ 
                'fieldToSave' => $result->fieldToSave,
                "type" => $result->type,
                'source' => $result->source,
                ];                
            if ($generateType == 'ListOnly') $arrcombo = array_merge($arrcombo, [ 'disabled' => $row->IsDisabledEdit == true ? true : false ]);   
            if ($generateType != 'ListOnly') $arr = array_merge($arr, $arrcombo);   
            if ($generateType == 'ListOnly' && $row->IsInlineEdit) $arr = array_merge($arr, ['editType' => [$arrcombo]]);
        } elseif ($row->FieldType == 'char') {
            if ($result->type == 'combobox') {
                // dd($row->APITableComboObj);
                $arrcombo = [ 
                    'fieldToSave' => $result->fieldToSave,
                    "type" => $result->type,
                    'source' => strtolower($result->source),
                    "onClick" => [
                        "action" => "request",
                        "store" => strtolower($result->source),
                        'params' => isset($row->ComboParams) ? json_decode($row->ComboParams) : null ]
                    ];
            } else {
                $arrcombo = [ 'source' => [], 'store' => $result->source];
                if ($row->ComboParams) $arrcombo = array_merge($arrcombo, [ 'params' => json_decode($row->ComboParams) ]);
            }
            $arrcombo = array_merge($arrcombo, [ 'hiddenField' => $result->fieldToSave.'Name', ]);            
            if ($generateType == 'ListOnly') $arrcombo = array_merge($arrcombo, [ 'disabled' => $row->IsDisabledEdit == true ? true : false ]);   
            if ($generateType != 'ListOnly') $arr = array_merge($arr, $arrcombo);   
            if ($generateType == 'ListOnly' && $row->IsInlineEdit) $arr = array_merge($arr, ['editType' => [$arrcombo]]);           
        }

        if ($generateType == 'List_InlineEdt') {
            unset($arr['column']);
            unset($arr['overrideLabel']);
            unset($arr['overideLabel']);
        }
        
        // if (isset($row['ol'])) $arr = array_merge($arr, [ 'overrideLabel' =>$row['ol'], ]);
        
        return $arr;
        
    }    

    public function generateVueView(Request $request) {
        $return = [];
        
        $query = "SELECT * FROM sysmodule WHERE LCASE(Code)='".strtolower($request->query('code'))."' OR LCASE(Name)='".strtolower($request->query('code'))."'";
        $table = $this->dbConnection->select(DB::raw($query));
        
        //NON TAB - NON GROUP
        $fieldsMainPage = [];
        $apiTable = $this->getDataJSON($request->query('code'), 'all');
        $Oid = $apiTable->Oid;

        //CUSTOM FIELD
        $customField = $this->getCustomFieldSetting($apiTable->Oid,$apiTable->Code,$apiTable->Name);
        $criteriaTable = " AND APITable='".$Oid."' ";

        //TAB (INSIDE)
        $fieldTabs = [];
        $fieldsGroup = [];
        $tabs = DB::select("SELECT d.* FROM (
            SELECT MIN(Sequence) AS Sequence, LayoutTab FROM apitablefield  
            WHERE APITable = '{$Oid}' AND LayoutTab IS NOT NULL AND IFNULL(IsHideInput,FALSE) != TRUE
            GROUP BY LayoutTab
            ) AS d ORDER BY d.Sequence;");
        foreach($tabs as $tab){
            //TAB - GROUP
            $groups = DB::select("SELECT d.* FROM (
                SELECT MIN(Sequence) AS Sequence, LayoutGroup FROM apitablefield  
                WHERE APITable = '{$Oid}' AND LayoutTab = '{$tab->LayoutTab}'
                AND LayoutGroup IS NOT NULL AND IFNULL(IsHideInput,FALSE) != TRUE
                GROUP BY LayoutGroup
                ) AS d ORDER BY d.Sequence;");
            foreach($groups as $group) {
                $criteria = "AND tbf.LayoutGroup='{$group->LayoutGroup}' AND tbf.LayoutTab='{$tab->LayoutTab}'
                    AND tbf.IsActive = true ".$customField->Hide.$criteriaTable;
                $data = $this->generateVueListSub($criteria);

                $criteria = "AND tbf.LayoutGroup='{$group->LayoutGroup}' AND tbf.LayoutTab='{$tab->LayoutTab}' 
                    AND tbf.IsActive = true AND tbf.OnHideWhen IS NULL
                    ".$customField->Hide.$criteriaTable;
                $noHidden = $this->generateVueListSub($criteria);
                $hideWhen = null;
                $hideWhenIsSame = true;
                if ($noHidden->count() == 0) {
                    $criteria = "AND tbf.LayoutGroup='{$group->LayoutGroup}' AND tbf.LayoutTab='{$tab->LayoutTab}'
                        AND tbf.IsActive = true AND tbf.OnHideWhen IS NOT NULL
                        ".$customField->Hide.$criteriaTable;
                    $noHidden = $this->generateVueListSub($criteria);
                    foreach($noHidden as $row) {
                        if ($hideWhenIsSame) {
                            // logger($tab->LayoutTab.' '.$group->LayoutGroup.' '.$row->Code.' '.$row->OnHideWhen);
                            if (!$hideWhen) $hideWhen = $row->OnHideWhen;
                            elseif ($hideWhen == $row->OnHideWhen) continue;
                            else {
                                $hideWhen = null;
                                $hideWhenIsSame = false;
                            }
                        }   
                    }
                }
                if ($data) {
                    $tmp = $this->subGenerate($data, 'ViewOnly', $customField);
                    if (count($tmp) > 0) $fieldsGroup[] = [
                        'name' => $group->LayoutGroup,
                        'icon' => 'SettingsIcon',
                        'hideWhen' => $hideWhen ? (isJson($hideWhen) ? json_decode($hideWhen) : $hideWhen ) : null,
                        'fields' => $tmp,
                    ];
                }
            }
        }
        
        $fieldsGroup[] = [
            'name' => 'Information',
            "icon" => "SettingsIcon",
            "hideWhen" => null,
            'fields' => [
                    [
                        "fieldToSave" => "CreatedByName",
                        "type" => "ViewOnly",
                        "overrideLabel" => "Created By",
                        "column" => "1/4"
                    ],
                    [
                        "fieldToSave" => "CreatedAt",
                        "type" => "ViewOnly",
                        "overrideLabel" => "Created At",
                        "column" => "1/4"
                    ],
                    [
                        "fieldToSave" => "UpdatedByName",
                        "type" => "ViewOnly",
                        "overrideLabel" => "Updated By",
                        "column" => "1/4"
                    ],
                    [
                        "fieldToSave" => "UpdatedAt",
                        "type" => "ViewOnly",
                        "overrideLabel" => "Updated At",
                        "column" => "1/4"
                    ],
                ]
        ];

        $tmpcriteria = '1=2';
        if ($apiTable->IsUsingModuleApproval) $tmpcriteria = "tb.Code='pubapproval'";
        $query = "SELECT tb.Oid, tb.Code, tb.Name, tb.APITableParentRelationshipName, tb.Title,
            COUNT(tbf.Oid) AS Counta, tb.IsDisabledCreate, tb.IsDisabledEdit, 
            tb.MultiButton, tb.APISaveNotBatch, tb.APIDeleteNotBatch, tb.OnHideWhen, tb.ActionDropDownRow
            FROM apitable tb LEFT OUTER JOIN apitablefield tbf ON tb.Oid = tbf.APITable
            WHERE (tb.APITableParent = '{$Oid}' OR ".$tmpcriteria.") AND tb.IsActive = true AND tb.Name IS NOT NULL
            GROUP BY tb.Oid, tb.Code, tb.Name, tb.APITableParentRelationshipName ORDER BY Counta DESC";
        $dataDetails = DB::select($query);
        foreach($dataDetails as $dataDetail) {
            $customFieldDtl = $this->getCustomFieldSetting($dataDetail->Oid,$dataDetail->Code,$dataDetail->Name);

            $criteria = "AND tbf.IsActive = TRUE AND tbf.APITable = '{$dataDetail->Oid}' AND tbf.Code != 'Company' ".$customFieldDtl->Hide;
            $data = $this->generateVueListSub($criteria);
            if ($data) {
                $arr = [
                    'name' => $dataDetail->APITableParentRelationshipName, //$dataDetail->Title ?: 
                    'icon' => 'SettingsIcon',
                    'column' => '1/1',
                ];
                // if (isset($dataDetail->OnHideWhen)) $arr = array_merge($arr,[ 'hideWhen' => [
                //     "add", json_decode($dataDetail->OnHideWhen)
                //  ] ]);
                // else $arr = array_merge($arr,[ 'hideWhen' => "add" ]);
            }

            $criteria = "AND tbf.IsActive = TRUE ABD tbf.IsListShowPrimary = TRUE AND tbf.APITable = '{$dataDetail->Oid}' AND tbf.Code != 'Company' ".$customFieldDtl->Hide;
            $data = $this->generateVueListSub($criteria);
            if ($data) {  
                $tmp = $this->subGenerate($data, 'ListOnly', $customFieldDtl);
                if (count($tmp) > 0) {
                    $arr = array_merge($arr,[ 
                    'fields' => [
                            [                        
                            'name' => $dataDetail->APITableParentRelationshipName, //$dataDetail->Title ?: 
                            'icon' => 'SettingsIcon',
                            'type' => 'table',
                            'list' => $tmp
                        ]
                    ]
                 ]);
                }
            }
                            
            if ($arr) $fieldsGroup[] = $arr;
        }         

        if ($apiTable->IsUsingModuleComment) {
            $arr = [
                "name" => "Comments",
                "icon" => "SettingsIcon",
                "column" => "1/1",
                "fields" => [                 
                    [
                        'name' => 'Comments',
                        'type' => 'table',
                        'icon' => 'CommentIcon', 
                        'post' => 'publiccomment/create?Oid={Oid}&Type='.$apiTable->Name,
                        'list' => [
                            [
                                'field' => "CreatedAt",
                                'name' => "Created At",
                                'width' => 100,
                            ],
                            [
                                'field' => "UserName",
                                'width' => 100,
                            ],
                            [
                                'field' => "Message",
                                'width' => 300,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Comments',
                        'fieldToSave' => 'Comments',
                        'type' => 'comments',
                        'icon' => 'CommentIcon', 
                        'post' => 'publiccomment/create?Oid={Oid}&Type='.$apiTable->Name,
                    ],
                ]
            ];
            $fieldsGroup[] = $arr;
        }

        if ($apiTable->IsUsingModuleImage) {
            $arr = [                
                "name" => "Images",
                "icon" => "SettingsIcon",
                "column" => "1/1",
                "fields" => [
                    [
                        'name' => 'Images',
                        'type' => 'table',
                        'icon' => 'ImageIcon',
                        'post' => 'image?Oid={Oid}&Type='.$apiTable->Name,
                        'delete' => 'image/{Oid}',
                        'list' => [
                            [
                                'field' => "CreatedAt",
                                'name' => "Uploaded At",
                                'width' => 150,
                            ],
                            [
                                'field' => "Image",
                                'type' => "image",
                                'width' => 300,
                            ]
                        ]
                    ],
                    [
                        'name' => 'Images',
                        'fieldToSave' => 'Images',
                        'type' => 'gallery',
                        'icon' => 'ImageIcon',
                        'post' => 'image?Oid={Oid}&Type='.$apiTable->Name,
                        'delete' => 'image/{Oid}',
                    ],
                ]
            ];
            $fieldsGroup[] = $arr;
        } 

        if ($apiTable->IsUsingModuleFile) {
            $arr = [                
                "name" => "Files",
                "icon" => "SettingsIcon",
                "column" => "1/1",
                "fields" => [
                    [
                        'name' => 'Files',
                        'type' => 'table',
                        'icon' => 'FilePlusIcon',  
                        'post' => 'file/upload?Oid={Oid}&Type='.$apiTable->Name,
                        'delete' => 'file/{Oid}',
                        'list' => [
                            [
                                'field' => "CreatedAt",
                                'name' => "Uploaded At",
                                'width' => 100,
                            ],
                            [
                                'field' => "FileName",
                                'type' => "url",
                                'width' => 500,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Files',
                        'fieldToSave' => 'Files',
                        'type' => 'files',
                        'icon' => 'FilePlusIcon',  
                        'post' => 'file/upload?Oid={Oid}&Type='.$apiTable->Name,
                        'delete' => 'file/{Oid}'
                    ],  
                ]            
            ];
            $fieldsGroup[] = $arr;
        }

        
        if (count($fieldsGroup) > 0) return [
            "main" => [
                "name" => "Main",
                "icon" => "SettingsIcon",
                "editButton" => "false",
                "hideWhen" => null,
                // "multiButton" => [
                //     [                         
                //         "name" => "Preview Report",
                //         "type" => "open_report",
                //         "icon" => "PrinterIcon",
                //         "get" => $table->ReportAPI."?action=preview",
                //         "params" => []
                //     ], [                         
                //         "name" => "Download Report",
                //         "type" => "download_report",
                //         "icon" => "PrinterIcon",
                //         "get" => $table->ReportAPI."?action=download",
                //         "params" => []
                //     ]
                // ],
                "fieldGroups"=> $fieldsGroup
            ]
        ];

        return $return;
    }
}
