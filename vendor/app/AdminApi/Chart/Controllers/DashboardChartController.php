<?php

namespace App\AdminApi\Chart\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Pub\Entities\PublicDashboard;
use App\Core\Pub\Entities\PublicDashboardDetail;
use App\Core\Internal\Entities\Role;
use Carbon\Carbon;
use App\Core\Base\Services\HttpService;
use App\AdminApi\Development\Controllers\ServerDashboardController;

class DashboardChartController extends Controller
{
    private $httpService;
    private $serverDashboardController;
    public function __construct()
    {
        $this->httpService = new HttpService();
        $this->httpService->baseUrl('http://api1.ezbooking.co:888')->json();
        $this->serverDashboardController = new ServerDashboardController();
    }

    public function getListQuery(Request $request) {
        return $this->serverDashboardController->functionGetTemplateCombo('combo');
    }

    public function generate(Request $request) {
        $user = Auth::user();
        $dashboard = ''; //68f6c107-690e-41cc-8ca4-1fb04fc363e1
        $dashboardTemplates = $this->serverDashboardController->functionGetTemplateData();
        if ($request->has('dashboard')) $dashboard = $request->input('dashboard'); //'da21ce60-96d4-430c-b541-b50e23c71d12'
        if (!$dashboard) $dashboard = Role::findOrFail($user->Role)->PublicDashboard;
        if (!$dashboard) $dashboard = PublicDashboard::first();

        $data = null;
        if ($dashboard) {
            $dashboard = PublicDashboardDetail::where('PublicDashboard',$dashboard)
                ->with('PublicDashboardObj')
                ->orderBy('Sequence')
                ->get();
            $data = [];
            foreach ($dashboard as $row) {
                $object = null;
                if ($row->DashboardTemplate) {
                    foreach($dashboardTemplates as $template) if ($template->Code == $row->DashboardTemplate) {
                        $object = $template;
                        $object->Sequence = $row->Sequence;
                    }
                }
                if ($object == null) $object = $row;
                switch ($object->ChartType) {
                    case 'SquareArea':
                        $data[] = $this->chartAreaSquare($object,'square');
                        break;
                    case 'TitleArea':
                        $data[] = $this->chartAreaSquare($object,'title');
                        break;
                    case 'LandscapeArea':
                        $data[] = $this->chartAreaSquare($object,'landscape');
                        break;
                    case 'Pie':
                        $data[] = $this->chartPie($object);
                        break;
                    case 'Bar':
                        $data[] = $this->chartBar($object);
                        break;
                    // case 'Line':
                    //     $data[] = $this->chartLine($object);
                    //     break;
                    case 'ListBulletin':
                        $data[] = $this->chartListBulletin($object);
                        break;
                    case 'List':
                        $data[] = $this->chartList($object);
                        break;
                }
            }
            $object = null;
            if ($user->CompanyObj->Code == 'dev_pos') {
                $data [] = $this->chartTimeline($object);
                $data [] = $this->chartPie2($object);
                $data [] = $this->chartHighlight($object);
                $data [] = $this->chartPieMeter($object);
                $data [] = $this->chartSpeedMeter($object);
            }
        }

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function chartAreaSquare($row, $type = 'square')
    {
        if (gettype($row) == 'array') $row = json_decode(json_encode($row), FALSE);
        if ($row->Query) $query = $row->Query;
        $user = Auth::user();
        $query = str_replace("[user]",$user->Oid,$query);

        $data = DB::select($query);
        
        if ($type == 'title') {
            return [
                'Sequence' => $row->Sequence,
                'ChartType' => 'SquareTitle',
                'DataType' => isset($row->DataType) ? $row->DataType : 'Query',
                'Title' => $row->Title,
                'Icon' => $row->Icon,
                'Color' => $row->Color,
                'Url' => $row->Url,
                'Data' => [
                    'Statistic' => $this->sumTotal($data),
                    'StatisticTitle' => $this->getv($row,'Title'),
                ],
            ];
        } else {
            return [
                'Sequence' => $row->Sequence,
                'ChartType' => $type == 'square' ? 'SquareArea' : 'LandscapeArea',
                'DataType' => isset($row->DataType) ? $row->DataType : 'Query',
                'Title' => $row->Title,
                'Icon' => $row->Icon,
                'Color' => $row->Color,
                'Url' => $row->Url,
                'Data' => [
                    'Statistic' => $this->sumTotal($data),
                    'StatisticTitle' => $row['Title'] ?: "Sales Revenue",
                    'Series' => [
                        [
                            'Name' => $row->Subtitle ?: "Sales",
                            'Data' => $this->arrayLast7DaysTemp($data)
                        ],
                    ]
                ],
            ];
        }
    }

    public function chartListBulletin($row)
    {
        if (gettype($row) == 'array') $row = json_decode(json_encode($row), FALSE);
        if ($row->Query) $query = $row->Query;
        $user = Auth::user();
        $query = str_replace("[user]",$user->Oid,$query);
        $data = DB::select($query);
        return [
            'Sequence' => $row->Sequence,
            'ChartType' => 'ListBulletin',
            'DataType' => isset($row->DataType) ? $row->DataType : 'Query',
            'Title' => $row->Title,
            'Icon' => $row->Icon,
            'Color' => $row->Color,
            'Url' => $row->Url,
            'Data' => $data
        ];
    }

    private function getv($data, $field) {
        if (!$data) return null;
        if (isset($data->{$field})) {
            if ($data->{$field} == ' - ') return $data[$field];
            else return $data->{$field};
        }
        if (isset($data[$field])) {
            return $data[$field];
        }
        return null;
    }

    private function chartPie($row)
    {        
        if (gettype($row) == 'array') $row = json_decode(json_encode($row), FALSE);
        $field = $row->Keyword;
        switch ($row->Sum) {
            case "amount": { $sum = 'p.TotalAmount'; break; }
            case "quantity": { $sum = 'p.Quantity'; break; }
            default: $sum = "p.TotalAmount";
        }
        $field = 'Warehouse';
        switch ($field) {
            case "Sales": { $join = 'LEFT OUTER JOIN mstemployee a ON a.Oid = p.Employee'; break; }
            case "Warehouse": { $join = 'LEFT OUTER JOIN mstwarehouse a ON a.Oid = p.Warehouse'; break; }
            case "Customer": { $join = 'LEFT OUTER JOIN mstbusinesspartner a ON a.Oid = p.Customer'; break; }
            case "POSTable": { $join = 'LEFT OUTER JOIN postable a ON a.Oid = p.POSTable'; break; }
            case "PaymentMethod": { $join = 'LEFT OUTER JOIN mstpaymentmethod a ON a.Oid = p.PaymentMethod'; break; }
            case "Supplier": { $join = 'LEFT OUTER JOIN mstbusinesspartner a ON a.Oid = p.Supplier'; break; }
            default:
                $join = 'LEFT OUTER JOIN user a ON a.Oid = p.User';
        }

        if($field == "ItemGroup") {
            $query = "SELECT ig.Oid AS GroupID, ig.Name AS GroupName,
                DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$sum.") AS Amount
                FROM pospointofsale p
                LEFT OUTER JOIN pospointofsaledetail pd ON pd.PointOfSale = p.Oid
                LEFT OUTER JOIN mstitem i ON i.Oid = pd.Item
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND i.".$field." IS NOT NULL
                GROUP BY i.".$field." ORDER BY Date;";
        }else {
            $query = "SELECT a.Oid AS GroupID, a.Name AS GroupName,
                DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$sum.") AS Amount
                FROM pospointofsale p
                ".$join."
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND p.".$field." IS NOT NULL
                GROUP BY p.".$field." ORDER BY Date;";
        }
        if ($row->DataType == 'Query') $query = $row->Criteria;
        $user = Auth::user();
        $query = str_replace("[user]",$user->Oid,$query);
        $data = DB::select($query);

        $dataArray = $this->arrayLastDays($data);

        $key= array();
        $key = [];
        $count = [];
        foreach ($dataArray as $rw) {
            $count[]=$rw['Count'];
            $key[]=$rw['Key'];
        }

        return [
            'Sequence' => $row->Sequence,
            'ChartType' => $row->ChartType,
            'DataType' => $row->DataType,
            'Title' => $row['Title'],
            'Icon' => $row->Icon,
            'Color' => $row->Color,
            'Url' => $row->Url,
            'Data' => [
                'Title' => $row['Title'] ?: "Customer",
                'AnalyticsData' => $dataArray,
                'Series' => $count,
                'Labels' => $key
            ],
        ];
    }

    private function chartBar($row)
    {
        if (gettype($row) == 'array') $row = json_decode(json_encode($row), FALSE);
        $key = $row->Keyword;
        switch ($row->Sum) {
            case "amount": { $field = 'p.TotalAmount'; break; }
            case "quantity": { $field = 'p.Quantity'; break; }
        }

        switch ($row->Keyword) {
            case "Warehouse": { $join = 'LEFT OUTER JOIN mstwarehouse a ON a.Oid = p.Warehouse'; break; }
            case "Customer": { $join = 'LEFT OUTER JOIN mstbusinesspartner a ON a.Oid = p.Customer'; break; }
            case "User": { $join = 'LEFT OUTER JOIN user a ON a.Oid = p.User'; break; }
            case "POSTable": { $join = 'LEFT OUTER JOIN postable a ON a.Oid = p.POSTable'; break; }
            case "PaymentMethod": { $join = 'LEFT OUTER JOIN mstpaymentmethod a ON a.Oid = p.PaymentMethod'; break; }
            case "Supplier": { $join = 'LEFT OUTER JOIN mstbusinesspartner a ON a.Oid = p.Supplier'; break; }
        }

       if($key == "ItemGroup") {
            $query = "SELECT ig.Oid AS GroupID, ig.Name AS GroupName,
                DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
                FROM pospointofsale p
                LEFT OUTER JOIN pospointofsaledetail pd ON pd.PointOfSale = p.Oid
                LEFT OUTER JOIN mstitem i ON i.Oid = pd.Item
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND i.".$key." IS NOT NULL
                GROUP BY i.".$key." ORDER BY Date;";
        }else {
            $query = "SELECT a.Oid AS GroupID, a.Name AS GroupName,
                DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
                FROM pospointofsale p
                ".$join."
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND p.".$key." IS NOT NULL
                GROUP BY p.".$key." ORDER BY Date;";
        }
        if ($row->DataType == 'Query') $query = $row->Criteria;
        $user = Auth::user();
        $query = str_replace("[user]",$user->Oid,$query);

        $data = DB::select($query);

        $dataArray = $this->arrayLastDays($data,$key);

        $key= array();
        $key = [];
        $count = [];
        $color = [];
        foreach ($dataArray as $rw) {
            $count[] = $rw['Count'];
            $key[] = $rw['Key'];
            $color[] = $rw['Color'];
        }

        return [
            'Sequence' => $row->Sequence,
            'ChartType' => $row->ChartType,
            'DataType' => $row->DataType,
            'Title' => $row['Title'],
            'Icon' => $row->Icon,
            'Color' => $row->Color,
            'Url' => $row->Url,
            'Data' => [
                'Title' => $row['Title'] ?: "Bar Horizontal",
                'Label' => $row->Subtitle ?: "Price",
                'Color' => $color,
                'Data' => $count,
                'Labels' => $key
            ],
        ];
    }

    public function chartLine($row)
    {
        if (gettype($row) == 'array') $row = json_decode(json_encode($row), FALSE);
        $criteria ='';
        switch ($row->Sum) {
            case "amount": { $field = 'p.TotalAmount'; break; }
            case "quantity": { $field = 'p.Quantity'; break; }
        }
        if ($row->Criteria) $criteria = " AND ".$row->Criteria;
        $query = "SELECT DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
                FROM pospointofsale p
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -2 WEEK) AND p.Date <= NOW() ".$criteria."
                GROUP BY DATE_FORMAT(Date, '%Y%m%d') ORDER BY Date";
        $data = DB::select($query);

        // $query = "SELECT DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
        //         FROM pospointofsale p
        //         WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -1 MONTH) AND p.Date <= NOW() ".$criteria."
        //         GROUP BY DATE_FORMAT(Date, '%Y%m%d') ORDER BY Date";
        // $data = DB::select($query);

        $query1 = "SELECT DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
                FROM pospointofsale p
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -1 WEEK) AND p.Date <= NOW() ".$criteria."
                GROUP BY DATE_FORMAT(Date, '%Y%m%d') ORDER BY Date";
        $data1 = DB::select($query1);

        // $query1 = "SELECT DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
        //         FROM pospointofsale p
        //         WHERE p.GCRecord IS NULL AND p.Date >= date_format(NOW(), '%Y-%m-01') AND p.Date <= NOW() ".$criteria."
        //         GROUP BY DATE_FORMAT(Date, '%Y%m%d') ORDER BY Date";
        // $data1 = DB::select($query1);
        return [
            'Sequence' => $row->Sequence,
            'ChartType' => $row->ChartType,
            'DataType' => $row->DataType,
            'Title' => $row['Title'],
            'Icon' => $row->Icon,
            'Color' => $row->Color,
            'Url' => $row->Url,
            'Data' => [
                'Statistic' => $this->sumTotal($data),
                'StatisticTitle' => $row['Title'] ?: "Revenue",
                'Series' => [
                    [
                        'Name' => $row->Subtitle ?: "This Week",
                        'Data' => $this->arrayLastThisWeeks($data1),
                        'Total' => array_sum($this->arrayLastThisWeeks($data1)),
                    ],
                    [
                        'Name' => $row->Subtitle2 ?: "Last Two Week",
                        'Data' => $this->arrayLast2Weeks($data),
                        'Total' => array_sum($this->arrayLast2Weeks($data)),
                    ],
                ],
            ],
        ];
    }

    public function chartTimeline($row) {
        return [
            "Sequence" => 99,
            "ChartType" => 'Timeline',
            "DataType" => 'Query',
            "Title" => 'Activity Timeline',
            "Icon" => "shopping-bag",
            "Color" => "success",
            "Url" => null,
            "Data" => [
                [
                    "Title"=>'Client Meeting',
                    "Description"=>'Bonbon macaroon jelly beans gummi bears jelly lollipop apple',
                    "Time"=>'25 mins Ago',
                    "Icon" => "shopping-bag",
                    "Color" => "success",
                ],
                [
                    "Title"=>'Email Newsletter',
                    "Description"=>'Bonbon macaroon jelly beans gummi bears jelly lollipop apple',
                    "Time"=>'25 mins Ago',
                    "Icon" => "shopping-bag",
                    "Color" => "warning",
                ],
                [
                    "Title"=>'Plan Webinar',
                    "Description"=>'Bonbon macaroon jelly beans gummi bears jelly lollipop apple',
                    "Time"=>'25 mins Ago',
                    "Icon" => "shopping-bag",
                    "Color" => "primary",
                ],
                [
                    "Title"=>'Launch Website',
                    "Description"=>'Bonbon macaroon jelly beans gummi bears jelly lollipop apple',
                    "Time"=>'25 mins Ago',
                    "Icon" => "shopping-bag",
                    "Color" => "success",
                ],
                [
                    "Title"=>'Marketing',
                    "Description"=>'Bonbon macaroon jelly beans gummi bears jelly lollipop apple',
                    "Time"=>'25 mins Ago',
                    "Icon" => "shopping-bag",
                    "Color" => "danger",
                ],
            ],
        ];
    }

    public function chartPie2($row) {
        return [
            "Sequence" => 101,
            "ChartType" => 'ChartPie2',
            "DataType" => 'Query',
            "Title" => 'Customers',
            "Icon" => "shopping-bag",
            "Color" => "success",
            "Url" => null,
            "analyticsData" => [
                [ 'customerType'=> 'New', 'counts'=> 890, "color"=> 'primary' ],
                [ 'customerType'=> 'Returning', 'counts'=> 258, "color"=> 'warning' ],
                [ 'customerType'=> 'Referrals ', 'counts'=> 149, "color"=> 'danger' ],
            ],
            "series"=> [690, 258, 999],
            "labels"=> ['New', 'Returning', 'Referrals'],
            "colors"=> ['#7961F9', '#FF9F43', '#EA5455'],
            "gradientToColors"=> ['#9c8cfc', '#FFC085', '#f29292'],
        ];
    }

    public function chartPieMeter($row) {
        return [
            "Sequence" => 103,
            "ChartType" => 'ChartPieMeter',
            "DataType" => 'Query',
            "Title" => 'Product Orders',
            "Icon" => "shopping-bag",
            "Color" => "success",
            "Url" => null,
            "Data" => [
                'analyticsData' => [
                    [ 'orderType' => 'Finished', 'counts' => 23043, 'color'=> 'primary' ],
                    [ 'orderType' => 'Pending', 'counts' => 14658, 'color'=> 'warning' ],
                    [ 'orderType' => 'Rejected ', 'counts' => 4758, 'color'=> 'danger' ],
                ],
                'series' => [70, 52, 26],
                'labels' => ['Finished', 'Pending', 'Rejected'],    
                'colors'=> ['#7961F9', '#FF9F43', '#f29292'],    
                'gradientToColors' => ['#9c8cfc', '#FFC085', '#EA5455'],
            ],
        ];
    }

    public function chartHighlight($row) {
        return [
            "Sequence" => 102,
            "ChartType" => 'ChartHighlight',
            "DataType" => 'Query',
            "Title" => '2.7k',
            "Subtitle" => 'Avg Sessions',
            "Amount" => 5.2,
            "AmountVersus" => 'vs Last 7 Days',
            "ActionUrl" => 'purchaseorder',
            "Icon" => "shopping-bag",
            "Color" => "success",
            "Url" => null,
            "FooterMax" => 100,
            "Data" => [
                'series' => [
                    [
                        'name' => 'Sessions',
                        'data' => [75, 125, 225, 175, 125, 75, 25]
                    ]
                ],
                'colors' => ['#e6edf7', '#e6edf7', '#7367f0', '#e6edf7', '#e6edf7', '#e6edf7'],
            ],
            'footer' => [
                [
                    "Title"=>'Goal: $10000',
                    "Amount" => 70,
                    "Color" => "danger",
                ],
                [
                    "Title"=>'Users: 100K',
                    "Amount" => 30,
                    "Color" => "danger",
                ],
                [
                    "Title"=>'Retention: 90%',
                    "Amount" => 20,
                    "Color" => "danger",
                ],
                [
                    "Title"=>'Duration: 1yr',
                    "Amount" => 80,
                    "Color" => "danger",
                ],
            ]
        ];
    }

    public function chartSpeedMeter($row) {
        return [
            "Sequence" => 100,
            "ChartType" => 'ChartSpeedMeter',
            "DataType" => 'Query',
            "Title" => 'Goal Overview',
            "Icon" => "shopping-bag",
            "Color" => "success",
            "Url" => null,
            "Data" => [
                'Title1' => 'Completed',
                'Amount1' => 786617,
                'Title2' => 'In Progress',
                'Amount2' => 13561,
                'series' => [83],
                'labels'=> ['Completed Tickets'],
                'chartData'=> [
                    'totalTickets' => 163,
                    'openTickets' => 103,
                    'lastResponse' => '1d',
                ],
            ],
        ];
    }
    
    public function chartList($row)
    {
        if (gettype($row) == 'array') $row = json_decode(json_encode($row), FALSE);
        $criteria ='';
        if ($row->Criteria) $criteria = " AND ".$row->Criteria;
        $query = "SELECT DATE_FORMAT(p.Date, '%Y-%m-%d') AS DateStart, DATE_FORMAT(p.Ended, '%Y-%m-%d') AS DateEnded, SUM(p.Amount) AS Amount, s.Name AS StatusName
            FROM possession p
            LEFT OUTER JOIN sysstatus s ON s.Oid = p.Status
            WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND p.Ended IS NOT NULL ".$criteria."
            GROUP BY DATE_FORMAT(p.Date, '%Y%m%d') ORDER BY p.Date";
        if ($row->DataType == 'Query') $query = $row->Criteria;
        $user = Auth::user();
        $query = str_replace("[user]",$user->Oid,$query);    
        $data = DB::select($query);

        $query2 = "SELECT u.Name, u.Image
            FROM possession p
            LEFT OUTER JOIN user u ON u.Oid = p.User
            WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND p.Ended IS NOT NULL ".$criteria."
            GROUP BY p.User ORDER BY p.Date";
        $data2 = DB::select($query2);
        
        foreach ($data as $dtl) {
            // logger($dtl->StatusName);
            if ($dtl->StatusName == "ENTRY") $dtl->StatusColor = "success";
            if ($dtl->StatusName == "PAID") $dtl->StatusColor = "danger";
            if ($dtl->StatusName == "CANCELLED") $dtl->StatusColor = "warning";
            $dtl->UsersLiked = $data2;
        }
        return [
            'Sequence' => $row->Sequence,
            'ChartType' => $row->ChartType,
            'DataType' => $row->DataType,
            'Title' => $row['Title'],
            'Icon' => $row->Icon,
            'Color' => $row->Color,
            'Url' => $row->Url,
            'Data' => [
                array_merge(['Title' => $row['Title']], [
                'Details' => $data
                ]),
            ],
        ];
    }

    private function arrayLastDays($data) {
        $arr = array();
        $i = 0;
       
        foreach($data as $row) {
            logger($row->Amount);
            array_push($arr, [
                'Key' => $row->GroupName,
                'Count' => $row->Amount,
                'Color' => getChartColor($i),
            ]);
            $i += 1;             
        }
        return $arr;
    }

    private function sumTotal($data) {
        if (gettype($data) == 'array') $data = json_decode(json_encode($data), FALSE);
        $totalamount = 0;        
        foreach($data as $row) {
            $totalamount += isset($row->Amount) ? $row->Amount : 0;
        }
        return $totalamount;
    }


    private function arrayLast7DaysTemp($data) {
        $arr = array();
        for ($x = 6; $x >= 0; $x--) {
            $found = 0;
            $date = date('Y-m-d', strtotime(now(). ' - '.$x.'days'));
            foreach($data as $row) {
                if ($row->DateOrder == $date) {
                    array_push($arr, $row->Amount);
                    $found = 1;
                }                
            }
            if ($found == 0) {
                $date = date('j M',strtotime(substr($x,0,4).'-'.substr($x,4,2).'-'.substr($x,6,2)));
                array_push($arr, 0);
            }   
        }
        return $arr;
    }    

    private function arrayLast7Days($data) {
        $arr = array();
        for ($x = 6; $x >= 0; $x--) {
            $found = 0;
            $date = date('Y-m-d', strtotime(now(). ' - '.$x.'days'));
            foreach($data as $row) {
                if ($row->DateOrder == $date) {
                    array_push($arr, [
                        'x' => $row->DateDisplay,
                        'y' => $row->Amount,
                    ]);
                    $found = 1;
                }                
            }
            if ($found == 0) {
                $date = date('j M',strtotime(substr($x,0,4).'-'.substr($x,4,2).'-'.substr($x,6,2)));
                array_push($arr, [
                    'x' => date('d M', strtotime(now(). ' - '.$x.'days')),
                    'y' => 0,
                ]);
            }   
        }
        return $arr;
    }    

    private function arrayLast2Weeks($data) {
        $arr = array();
        $totalamount = 0;
        $dateend1 = date_format(NOW(),"Y-m-d");
        $datestart1 = date('Y-m-d', strtotime(now(). ' -2 week'));
        // $datestart1 = date('Y-m-d', strtotime(now(). ' -1 month'));

        for ($x = $datestart1; $x <= $dateend1; $x++) {
            $found = 0;
            foreach($data as $row) {
                if ($row->DateOrder == $x) {
                    array_push($arr, $row->Amount);
                    $totalamount += $row->Amount;
                    $found = 1;
                }
            }
            if ($found == 0) {
                array_push($arr, 0);
            }
            logger($arr);
        }
        return $arr;
    }

    private function arrayLastThisWeeks($data) {
        $arr = array();
        $totalamount = 0;
        $dateend1 = date_format(NOW(),"Y-m-d");
        $datestart1 = date('Y-m-d', strtotime(now(). ' -1 week'));

        for ($x = $datestart1; $x <= $dateend1; $x++) {
            $found = 0;
            foreach($data as $row) {
                if ($row->DateOrder == $x) {
                    array_push($arr, $row->Amount);
                    $totalamount += $row->Amount;
                    $found = 1;
                }                
            }
            if ($found == 0) {
                array_push($arr, 0);
            }
        }
        return $arr;
    }
}

//datatype backup
// [{"Oid":"Query","Name":"Query"},{"Oid":"POS","Name":"POS"},{"Oid":"Sales","Name":"Sales"},{"Oid":"Purchase","Name":"Purchase"},{"Oid":"Stock","Name":"Stock"},{"Oid":"Cashbank","Name":"Cashbank"},{"Oid":"Payment","Name":"Payment"},{"Oid":"Receipt","Name":"Receipt"}]
