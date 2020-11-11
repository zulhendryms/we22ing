<?php

namespace App\AdminApi\Development\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Pub\Entities\PublicPost;;
use App\Core\Internal\Entities\Status;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\ServerCRUDController;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Base\Services\HttpService;
use App\Core\Master\Entities\ReportLog;
use App\Core\Internal\Events\EventSendNotificationSocketOneSignal;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportGeneratorController extends Controller
{
    protected $reportService;
    private $CRUDController;
    public function __construct()
    {
        $this->CRUDController = new ServerCRUDController();
        $this->reportService = new ReportService();
        $this->httpService = new HttpService();
        $this->httpService->baseUrl('http://api1.ezbooking.co:888')->json();
    }

    public function config(Request $request)
    {
        $return = $this->httpService->get('/portal/api/development/table/phpreport?code=ReportStock');
        // return $return;
        return response()->json(
            $return,
            Response::HTTP_OK
        );
    }

    private function tableJoin($data, $tableSource, $tableTarget, $fieldName)
    {
        $tableAs = $tableSource . '_' . $fieldName;
        // logger('LEFT OUTER JOIN '.$tableTarget.' AS '.$tableAs.' = '.$tableSource.'.'.$fieldName);
        return $data . PHP_EOL . " LEFT OUTER JOIN " . $tableTarget . " AS " . $tableAs . " ON " . $tableAs . ".Oid=" . $tableSource . "." . $fieldName;
    }

    private function tableJoinDetail($data, $tableTarget, $fieldName)
    {
        // logger('LEFT OUTER JOIN '.$tableTarget.' AS d = p.'.$fieldName);
        return $data . PHP_EOL . " LEFT OUTER JOIN " . $tableTarget . " AS d ON p.Oid=d." . $fieldName;
    }

    private function tableWhere($data, $string)
    {
        return $data . PHP_EOL . " AND " . $string;
    }

    public function report(Request $request)
    {
        $report = $this->CRUDController->functionGetReport($request->input('report'));
        if ($report) $report = $report[0];
        else return null;
        $reportParent = $this->CRUDController->functionGetReportParent($report->ModuleReport)[0];
        $report->User = Auth::user();
        $tableData = $this->CRUDController->getDataJSON($reportParent->TableParent, 'all');
        $report->Columns = json_decode($report->Columns);
        $report->FieldsParent = json_decode($report->FieldsParent);
        if ($report->FieldsDetail) $report->FieldsDetail = json_decode($report->FieldsDetail);

        $data = " FROM " . $reportParent->TableParent . ' as p';

        // join parent table combo
        $fieldCombos = $this->CRUDController->functionGetFieldsComboFromTable($reportParent->TableParent, 'list');
        $joinTable = null;
        foreach ($fieldCombos as $combo) {
            $data = $this->tableJoin($data, 'p', $combo->TableName, $combo->FieldName);
            $joinTable = $joinTable . ($joinTable ? ",'" : "'") . $combo->TableName . "'";
        }
        // join parent table combo lvl 2
        $tableJoins = $this->CRUDController->functionGetReportTableJoin($joinTable);
        foreach ($tableJoins as $tableJoin) {
            $tmp = json_decode($tableJoin->ReportJoinTable);
            foreach ($fieldCombos as $combo) {
                //dd($tableJoin);   // +"Code": "mstbusinesspartner" +"Name": "BusinessPartner"
                //dd($combo);       // TableName mstitem TableComboCode Item FieldName PurchaseBusinessPartner
                // dd($row)         // field = +"field": "BusinessPartnerGroup"  +"table": "mstbusinesspartnergroup"
                if ($combo->TableName == $tableJoin->Code) {
                    foreach ($tmp as $row) {
                        $alias = "p_" . $combo->FieldName . "_" . $row->field;
                        $data = $data . PHP_EOL . " LEFT OUTER JOIN " . $row->table . " AS " . $alias .
                            " ON " . $alias . ".Oid=p_" . $combo->FieldName . "." . $row->field;
                    }
                }
            }
        }

        // join detail table combo
        if (isset($report->FieldsDetail)) {
            $data = $this->tableJoinDetail($data, $reportParent->TableDetail, $tableData->Name);
            $fieldCombos = $this->CRUDController->functionGetFieldsComboFromTable($reportParent->TableDetail, 'list');
            $joinTable = null;
            foreach ($fieldCombos as $combo) {
                if ($combo->TableName == 'company') continue;
                $data = $this->tableJoin($data, 'd', $combo->TableName, $combo->FieldName);
                $joinTable = $joinTable . ($joinTable ? ",'" : "'") . $combo->TableName . "'";
            }

            // join detail table combo lvl2
            $tableJoins = $this->CRUDController->functionGetReportTableJoin($joinTable);
            foreach ($tableJoins as $tableJoin) {
                $tmp = json_decode($tableJoin->ReportJoinTable);
                foreach ($tmp as $row) {
                    $data = $this->tableJoin($data, 'd_' . $tableJoin->Name, $row->table, $row->field);
                }
            }
        }

        //criteria
        $data = $data . PHP_EOL . " WHERE p.GCRecord IS NULL";
        $report->Filter = null;
        if ($request->has('DateFrom')) {
            $datefrom = Carbon::parse($request->input('DateFrom'));
            $report->Filter = $report->Filter . "Date From = '" . strtoupper($datefrom->format('Y-m-d')) . "'; ";
            $data = $this->tableWhere($data, "DATE_FORMAT(p.Date, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'");
        }
        if ($request->has('DateTo')) {
            $dateto = Carbon::parse($request->input('DateTo'));
            $report->Filter = $report->Filter . "Date Until = '" . strtoupper($dateto->format('Y-m-d')) . "'; ";
            $data = $this->tableWhere($data, "DATE_FORMAT(p.Date, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'");
        }
        if ($reportParent->ReportCriterias) {
            $reportParent->ReportCriterias = json_decode($reportParent->ReportCriterias);
            foreach ($reportParent->ReportCriterias as $field) {
                if (!$request->has($field->fieldToSave)) continue;
                if (in_array($field->fieldToSave, ['DateFrom', 'DateTo'])) continue;
                $report->Filter = $report->Filter . $field->fieldToSave . " = '" . $request->input($field->fieldToSave) . "'; ";
                $data = $this->tableWhere($data, $field->fieldToSave . ".Oid='" . $request->input($field->fieldToSave) . "'");
            }
        }

        //select
        $fieldsOnly = [];
        $selectFields = null;
        $group = "";
        if (in_array($report->ReportType, ['Summary'])) {
            $groupGroup = "";
            $groupDate = "";
            $groupField = "";
            // dd($report->FieldsParent);
            foreach ($report->FieldsParent as $field) {
                if (isset($field->IsFormula)) continue;
                if (isset($field->Sum)) $selectFields = $selectFields . PHP_EOL . ($selectFields ? ',' : '') . "SUM(IFNULL(" . $field->FieldDisplay . ",0)) AS " . $field->Alias;
                else {
                    $selectFields = $selectFields . PHP_EOL . ($selectFields ? ',' : '') . $field->FieldDisplay . " AS `" . $field->Alias . "`";
                    $group = $group . PHP_EOL . ($group ? ',' : '') . $field->FieldDisplay;
                    if (isset($field->Group)) {
                        $groupGroup = $groupGroup . ($groupGroup ? ',' : '') . $field->FieldDisplay;
                        if ($field->Field != $field->FieldDisplay) $groupGroup = $groupGroup . ($groupGroup ? ',' : '') . $field->Field;
                    } elseif (in_array($field->Name, ['Date', 'Period', 'Daily'])) {
                        $groupDate = $groupDate . ($groupDate ? ',' : '') . $field->FieldDisplay;
                        if ($field->Field != $field->FieldDisplay) $groupDate = $groupDate . ($groupDate ? ',' : '') . $field->Field;
                        // dd($groupDate);
                    } else {
                        $groupField = $groupField . ($groupField ? ',' : '') . $field->FieldDisplay;
                        if ($field->Field != $field->FieldDisplay) $groupField = $groupField . ($groupField ? ',' : '') . $field->Field;
                    }
                }
            }

            $group = $group . ($group ? ',' : '') . $groupGroup;
            $group = $group . ($group ? ',' : '') . $groupDate;
            $group = $group . ($group ? ',' : '') . $groupField;
            $group = PHP_EOL . "GROUP BY " . $group;
        } else {
            if ($report->FieldsParent) foreach ($report->FieldsParent as $field) {
                if (isset($field->IsFormula)) continue;
                $selectFields = $selectFields . PHP_EOL . ($selectFields ? ',' : '') . $field->FieldDisplay . " AS `" . $field->Alias . "`";
            }
            if ($report->FieldsDetail) foreach ($report->FieldsDetail as $field) {
                if (isset($field->IsFormula)) continue;
                $selectFields = $selectFields . PHP_EOL . ($selectFields ? ',' : '') . $field->FieldDisplay . " AS `" . $field->Alias . "`";
            }
        }

        //rearrange fields & grouping
        $fieldsSum = [];
        $fieldsSumColSpan = 0;
        $fieldSort = null;
        $i = 0;
        foreach ($report->FieldsParent as $field) {
            if (isset($field->Sum)) if ($field->Sum) {
                $fieldsSum = array_merge($fieldsSum, [$field->Alias => 0]);
                if ($fieldsSumColSpan < 1) $fieldsSumColSpan = $i;
            }
            $i = $i + 1;
        }
        foreach ($report->FieldsParent as $field) {
            if (isset($field->Group)) {
                if ($field->Group) {
                    $report->{'Group' . $field->Group} = $field;
                    $report->{'Group' . $field->Group}->Value = null;
                    $report->{'Group' . $field->Group}->ColSpan = $fieldsSumColSpan;
                    $report->{'Group' . $field->Group}->ColSpanHeader = $field->ColSpan;
                    $report->{'Group' . $field->Group}->Sum = $fieldsSum;
                    $fieldSort = $fieldSort . ($fieldSort ? ", " : "") . $field->FieldDisplay;
                }
            } else $fieldsOnly[] = $field;
        }
        $report->FieldsParent = $fieldsOnly;

        $fields = $this->CRUDController->functionGetFieldsFromTable($tableData->Code);
        $sort = null;
        if (!in_array($report->ReportType, ['Summary'])) {
            foreach ($fields as $row) if ($row->Code == 'Date') $sort = "p.Date";
            if ($sort) foreach ($fields as $row) if ($row->Code == 'Code') $sort = "p.Date, p.Code";
            if (!$sort) foreach ($fields as $row) if ($row->Code == 'Name') $sort = "p.Name";
            if (!$sort) $sort = "p.CreatedAt";
        }
        $sort = PHP_EOL . "ORDER BY " . $fieldSort . ($fieldSort && $sort ? ", " : "") . $sort;
        $query = "SELECT " . $selectFields . $data . $group . $sort;
        // dd($query);
        $data = DB::select($query);

        $report->Parent = null;
        $dataReport = $report;
        if ($request->input('action') == 'dev') return view('AdminApi\Pub::pdf.report1', compact('data', 'dataReport'));
        $pdf = SnappyPdf::loadView('AdminApi\Pub::pdf.report1', compact('data', 'dataReport'));
        $headerHtml = view('AdminApi\Pub::pdf.header', compact('dataReport'))->render();
        $footerHtml = view('AdminApi\Pub::pdf.footer', compact('dataReport'))->render();
        $pdf
            ->setOption('header-html', $headerHtml)
            ->setOption('footer-html', $footerHtml)
            ->setOption('footer-right', "Page [page] of [toPage]")
            ->setOption('footer-font-size', 5)
            ->setOption('footer-line', true)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10);

        $reportFile = $this->reportService->create('Temporary_Report_Generated_', $pdf);
        if ($request->input('action') == 'download') return response()->download($reportFile->getFilePath())->deleteFileAfterSend(true);
        return response()->json(
            route(
                'AdminApi\Report::view',
                ['reportName' => $reportFile->getFileName()]
            ),
            Response::HTTP_OK
        );
    }

    public function reportFields($table)
    {
        $reportParent = $this->CRUDController->functionGetReportParent($table)[0];
        $fields = $this->CRUDController->functionGetFieldsFromTable($reportParent->TableParent);
        $fieldParent = [];
        $fieldDetail = [];
        foreach ($fields as $field) {
            if (!$field->IsActive) continue;
            if ($field->IsImage) continue;
            elseif ($field->APITableCombo) {
                $fieldParent[] = [
                    'Field' => 'p_' . $field->Code . '.Oid',
                    'FieldDisplay' => 'p_' . $field->Code . '.' . $field->TableComboField,
                    'Alias'  => 'p_' . $field->Code,
                    'Name' => $field->Name
                ];
                if ($field->ReportJoinTable) {
                    $tmp = json_decode($field->ReportJoinTable);
                    foreach ($tmp as $row) {
                        $fieldParent[] = [
                            'Field' => 'p_' . $field->Code . '_' . $row->field . '.Oid',
                            'FieldDisplay' => 'p_' . $field->Code . '_' . $row->field . '.' . $field->TableComboField,
                            'Alias'  => 'p_' . $field->Code . '_' . $row->field,
                            'Name' => $field->Name . ' ' . $row->field
                        ];
                    }
                }
            } else $fieldParent[] = [
                'Field' => 'p.' . $field->Code,
                'FieldDisplay' => 'p.' . $field->Code,
                'Alias'  => 'p_' . $field->Code,
                'Name' => $field->Name
            ];
        }
        if ($reportParent->TableDetail) {
            $fields = $this->CRUDController->functionGetFieldsFromTable($reportParent->TableDetail);
            foreach ($fields as $field) {
                if (!$field->IsActive) continue;
                if ($field->IsImage) continue;
                elseif ($field->APITableCombo) {
                    $fieldDetail[] = [
                        'Field' => 'd_' . $field->Code . '.Oid',
                        'FieldDisplay' => 'd_' . $field->Code . '.' . $field->TableComboField,
                        'Alias'  => 'd_' . $field->Code,
                        'Name' => $field->Name
                    ];
                    if ($field->ReportJoinTable) {
                        $tmp = json_decode($field->ReportJoinTable);
                        foreach ($tmp as $row) {
                            $fieldDetail[] = [
                                'Field' => 'd_' . $field->Code . '_' . $row->field . '.Oid',
                                'FieldDisplay' => 'd_' . $field->Code . '_' . $row->field . '.' . $field->TableComboField,
                                'Alias'  => 'd_' . $field->Code . '_' . $row->field,
                                'Name' => $field->Name . ' ' . $row->field
                            ];
                        }
                    }
                } else $fieldDetail[] = [
                    'Field' => 'd.' . $field->Code,
                    'FieldDisplay' => 'd.' . $field->Code,
                    'Alias'  => 'd_' . $field->Code,
                    'Name' => $field->Name
                ];
            }
        }
        $fieldSpecial[] = (object) [
            "Field" => "DATE_FORMAT(p.Date, '%Y-%m-%d'), DATE_FORMAT(p.Date, '%d-%M-%Y')",
            "FieldDisplay" => "DATE_FORMAT(p.Date, '%d-%M-%Y')",
            "Alias" => "p_Date",
            "Name" => "Daily"
        ];
        $fieldSpecial[] = (object) [
            "Field" => "DATE_FORMAT(p.Date, '%Y-%m'), DATE_FORMAT(p.Date, '%m %Y')",
            "FieldDisplay" => "DATE_FORMAT(p.Date, '%m-%Y')",
            "Alias" => "p_Date",
            "Name" => "Period"
        ];
        $fieldSpecial[] = (object) [
            "IsFormula" => true,
            "FieldFormula" => [
                "DebetAmount", "*", "CreditAmount"
            ],
            "RunningSum" => "Group",
        ];
        return [
            "Parent" => $fieldParent,
            "Detail" => $fieldDetail,
            "SpecialFields" => $fieldSpecial,
        ];
    }

    public function ReportActionExport($reportPath)
    {
        $user = Auth::user();
        $key = $reportPath;
        $url = route('Core\Export::report', ['key' => $key]);

        $reportLog = new ReportLog;
        $reportLog->Company = $user->Company;
        $reportLog->URL = $url;
        $reportLog->FileName = $reportPath;
        $reportLog->save();

        return $url;
    }

    public function ReportActionEmail($to,$reportName,$reportPath)
    {
        $reportUrl = $this->ReportActionExport($reportPath);
        $param = (object) [
            "To" => $to,
            "Subject" => $reportName.now(),
            "Body" => "Please find enclosed report as below",
            "Url" => $reportUrl,
        ];        
        $reponse = $this->httpService->post('/portal/api/email/send', $param);        
        return $reportUrl;
    }

    public function ReportPost($reportName,$reportPath)
    {
        $user = Auth::user();
        $reportUrl = $this->ReportActionExport($reportPath);
        // $data = new PublicPost();
        // $data->Company = $user->Company;
        // $data->Description = now()->format('Y-m-d').' - '.$reportName;
        // $data->ObjectType = 'PostText';
        // $data->User = $user->Oid;
        // $data->IsPrivatePost = true;
        // $data->Action =  json_encode([
        //     'name' => 'Open',
        //     'icon' => 'ArrowUpRightIcon',
        //     'type' => 'download',
        //     'url' => $reportUrl
        // ]);
        // $data->save();
        // $reponse = $this->sendNotification($data);
        
        $param = [
            'User' => $user->Oid,
            'Company' => $user->Company,
            'Type' => 'Log',
            'ObjectType' => 'Report',
            'Icon' => 'DownloadIcon',
            'Color' => 'primary',
            'Code' => now()->format('Ymdhis'),
            'Title' => now()->format('Y-m-d').' - '.$reportName, //PR-20132 needs Approval
            'Message' => "Please click to download the report", //Approved by Victor (message)
            'Action' => [
                'name' => 'Open',
                'icon' => 'ArrowUpRightIcon',
                'type' => 'download',
                'url' => $reportUrl
            ]
        ];
        event(new EventSendNotificationSocketOneSignal($param));
        return $reportUrl;  
    }
}
