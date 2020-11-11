<?php

namespace App\AdminApi\Chart\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ChartController extends Controller
{
    public function chartpos(Request $request)
    {
        // $query = "SELECT DATE_FORMAT(Date, '%y-%m-%d') AS Date, SUM(p.TotalAmount) AS TotalAmount FROM pospointofsale p GROUP BY DATE_FORMAT(Date, '%y-%m-%d')";
        // $query = "SELECT SUM(p.TotalAmount) as TotalAmount FROM pospointofsale p GROUP BY DATE_FORMAT(Date, '%y-%m-%d')";
        // $data = DB::select($query);

        // $data=[350, 275, 400, 300, 350, 300, 450];

        $data = [
            'Statistic' => '97.5K',
            'StatisticTitle' => 'Revenue Generated',
            'Series' => [
                [
                    'Name' => 'Revenue',
                    'Data' => [350, 275, 400, 300, 350, 300, 450]
                ],
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function chartline(Request $request)
    {
        $data = [
            'Statistic' => '97.5K',
            'StatisticTitle' => 'Revenue Generated',
            'Series' => [
                [
                    'Name' => 'This Month',
                    'Data' => [45000, 47000, 44800, 47500, 45500, 48000, 46500, 48600],
                    'Total' => '86,589',
                ],
                [
                    'Name' => 'Last Month',
                    'Data' => [46000, 48000, 45500, 46600, 44500, 46500, 45000, 47000],
                    'Total' => '73,683',
                ],
            ],
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function chartbrowser(Request $request)
    {
        $data = [
            ['id' => '1','name' => 'Google Chrome','ratio' => '73','time' => 'Mon Dec 10 2018 07:46:05 GMT+0000 (GMT)','comparedResult' => '800'],
            ['id' => '3','name' => 'Opera','ratio' => '8','time' => 'Mon Dec 10 2018 07:46:05 GMT+0000 (GMT)','comparedResult' => '-200'],
            ['id' => '2','name' => 'Firefox','ratio' => '19','time' => 'Mon Dec 10 2018 07:46:05 GMT+0000 (GMT)','comparedResult' => '100'],
            ['id' => '4','name' => 'Internet Explorer','ratio' => '27','time' => 'Mon Dec 10 2018 07:46:05 GMT+0000 (GMT)','comparedResult' => '-450']
           
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function chartradialbar(Request $request)
    {
        $data = [
            ['series' =>[83],
            'details' =>[
                ['title'=> 'Complete', 'total'=> '786,617'],
                ['title'=> 'Progress', 'total'=> '13,561']]
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function chartretentionbar(Request $request)
    {
        $data = [
            ['series' =>[
                ['name'=> 'New Clients', 'data'=> [175, 125, 225, 175, 160, 189, 206, 134, 159, 216, 148, 123]],
                ['name'=> 'Retained Clients', 'data'=> [-144, -155, -141, -167, -122, -143, -158, -107, -126, -131, -140, -137]]],
            'details' =>[
                ['title'=> 'Goal'],
                ['title'=> 'Users']],
           ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function chartpie(Request $request)
    {
        $data = [
            ['analyticsData' =>[
                ['customerType'=> 'New', 'counts'=> 890, 'color'=>'primary' ],
                ['customerType'=> 'Returning','counts'=> 258,'color'=>'warning'],
                ['customerType'=> 'Referrals','counts'=> 149,'color'=>'danger'],
            ],'series' => [690, 258, 149],'labels' => ['New', 'Returning', 'Referrals']]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function chartsalesbar(Request $request)
    {
        $data = [
            ['series' =>[
                ['name'=> 'Sessions', 'data'=> [75, 125, 225, 175, 125, 75, 25]]],
            'details' =>[
                ['title'=> 'Goal', 'amount'=> '$100000','percent'=> '50','color'=> 'primary'],
                ['title'=> 'Users', 'amount'=> '100K','percent'=> '60','color'=> 'warning'],
                ['title'=> 'Retention', 'amount'=> ' 90%','percent'=> '70','color'=> 'danger'],
                ['title'=> 'Duration', 'amount'=> '1yr','percent'=> '90','color'=> 'success']],
            'total' => '2.7K','description' =>'Avg Sessions','percent'=>'+5.2%','last'=>'vs last 7 days']
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function charttimeline(Request $request)
    {
        $data = [
            ['title' => 'Client Meeting', 'description' => 'Bonbon macaroon jelly beans gummi bears jelly lollipop apple','time' => '25 mins ago'],
            ['title' => 'Email Newsletter', 'description' => 'Cupcake gummi bears soufflé caramels candy','time' => '15 days ago'],
            ['title' => 'Plan Webinar', 'description' => 'Candy ice cream cake. Halvah gummi bears','time' => '20 days ago'],
            ['title' => 'Launch Website', 'description' => 'Candy ice cream cake. Halvah gummi bears Cupcake gummi bears soufflé caramels candy.','time' => '25 days ago'],
            ['title' => 'Marketing', 'description' => 'Candy ice cream cake. Halvah gummi bears Cupcake gummi bears.','time' => '28 days ago']
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function linechart(Request $request)
    {
        $data = [
            ['labels' =>[1500, 1600, 1700, 1750, 1800, 1850, 1900, 1950, 1999, 2050],
            'datasets' =>[
                ['data'=>[86, 114, 106, 106, 107, 111, 133, 221, 783, 2478],'label'=> 'Africa'],
                ['data'=>[282, 350, 411, 502, 635, 809, 947, 1402, 3700, 5267],'label'=> 'Asia'],
                ['data'=>[168, 170, 178, 190, 203, 276, 408, 547, 675, 734],'label'=> 'Europe'],
                ['data'=>[40, 20, 10, 16, 24, 38, 74, 167, 508, 784],'label'=> 'Latin America'],
                ['data'=>[6, 3, 2, 2, 7, 26, 82, 172, 312, 433],'label'=> 'North America']],
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function lineareachart(Request $request)
    {
        $data = [
            ['series' =>[
                ['name'=> 'series1', 'data'=> [31, 40, 28, 51, 42, 109, 100]],
                ['name'=> 'series2', 'data'=> [11, 32, 45, 32, 34, 52, 41]]],
           ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function barchart(Request $request)
    {
        $data = [
            ['labels' =>["Africa", "Asia", "Europe", "Latin America", "North America"],
            'datasets' =>[
                ['data'=>[2478, 5267, 734, 784, 433],'label'=> 'Population (millions)']],
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function columchart(Request $request)
    {
        $data = [
            ['series' =>[
                ['name'=> 'Net Profit', 'data'=> [44, 55, 57, 56, 61, 58, 63, 60, 66]],
                ['name'=> 'Revenue', 'data'=> [76, 85, 101, 98, 87, 105, 91, 114, 94]],
                ['name'=> 'Free Cash Flow', 'data'=> [35, 41, 36, 26, 45, 48, 52, 53, 41]]],
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function piechart(Request $request)
    {
        $data = [
            ['labels' => ["Africa", "Asia", "Europe", "Latin America", "North America"],
            'datasets' =>[
                ['data'=>[2478, 5267, 734, 784, 433],'label'=> 'Population (millions)']],
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function doughnutchart(Request $request)
    {
        $data = [
            ['labels' => ["Africa", "Asia", "Europe", "Latin America", "North America"],
            'datasets' =>[
                ['data'=>[2478, 5267, 734, 784, 433],'label'=> 'Population (millions)']],
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }
}
