<?php

namespace App\AdminApi\Development\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class DevelopmentDashBoardController extends Controller
{
    private $crudController;
    private $dbConnection;
    public function __construct()
    {
        $this->dbConnection = DB::connection('server');
        $this->crudController = new CRUDDevelopmentController();
    }

    public function fields() {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'Oid'];
        $fields[] = ['w'=> 200, 'r'=>0, 't'=>'text', 'n'=>'Code'];
        $fields[] = ['w'=> 500, 'r'=>0, 't'=>'text', 'n'=>'Name'];
        $fields[] = ['w'=> 200, 'r'=>0, 't'=>'text', 'n'=>'ChartType'];
        return $fields;
    }


    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields(),false,true);
        $fields[0]['cellRenderer'] = 'actionCell';
        $fields[0]['topButton'] = [ $this->action(true)[0] ];
        return $fields;
    }

    public function list(Request $request)
    {
        $criteria = null;
        if ($request->has('search')) {
            $search = $request->input('search');
            $criteria = "WHERE Name LIKE '%{$search}%' OR Code LIKE '%{$search}%'";
        }
        return $this->subList($criteria, false);
    }

    private function subList($where = null, $noAction = true)
    {
        $query = "SELECT Oid, Code, Name, ChartType FROM ezb_server.apidashboardquery " . $where;
        $data = $this->dbConnection->select(DB::raw($query));
        if (!$noAction) foreach ($data as $row) {
            $row->DefaultAction = $this->action()[0];
            $row->Action = $this->action();
        }
        return $data;
    }

    public function presearch(Request $request)
    {
        return null;
    }

    public function index(Request $request)
    {
        return null;
    }
    private function action($isAdd = false)
    {        
        return [
            [
                'name' => 'Quick Edit',
                'icon' => 'PlusIcon',
                'type' => 'global_form',
                'showModal' => false,
                'get' => $isAdd ? null : 'development/dashboard/{Oid}',
                'portalpost' => 'development/savedashboard'.($isAdd ? '' : '/{Oid}'),
                'afterRequest' => "apply",
                'form' => [
                    [
                        'fieldToSave' => 'Code',
                        'type' => 'inputtext',
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'Name',
                        'type' => 'inputtext',
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'Title',
                        'type' => 'inputtext',
                        'column' => '1/2',
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'ChartType',
                        'type' => 'combobox',
                        'column' => '1/2',
                        'default' => null,
                        'onChange' => [
                            'link' => [
                                [
                                    'link' => 'ImageChartType'
                                ]
                            ]
                        ],
                        'source' => $this->listCharts()
                    ],
                    [
                        'fieldToSave' => 'Query',
                        'type' => 'inputarea',
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'Subtitle',
                        'type' => 'inputtext',
                        'column' => "1/2",
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'Subtitle2',
                        'type' => 'inputtext',
                        'column' => "1/2",
                        'default' => null
                    ],
                    [
                        'fieldToSave' => 'Color',
                        'type' => 'combobox',
                        'column' => '1/2',
                        'default' => null,
                        'source' => [
                            [
                                'Oid' => 'primary',
                                'Name' => 'Primary'
                            ],
                            [
                                'Oid' => 'warning',
                                'Name' => 'Orange'
                            ],
                            [
                                'Oid' => 'danger',
                                'Name' => 'Red'
                            ],
                            [
                                'Oid' => 'success',
                                'Name' => 'Green'
                            ],
                            [
                                'Oid' => 'dark',
                                'Name' => 'Black'
                            ]
                        ]
                    ],
                    [
                        'fieldToSave' => 'Icon',
                        'type' => 'combobox',
                        'column' => '1/2',
                        'default' => null,
                        'source' => $this->listIcons()
                    ],
                    [
                        'fieldToSave' => 'Url',
                        'type' => 'inputtext',
                        'default' => null
                    ],
                ]
            ],            
            [
                'name' => 'Delete',
                'icon' => 'TrashIcon',
                'type' => 'confirm',
                'delete' => 'development/dashboard/{Oid}',
            ]
        ];
    }

    public function show($data)
    {
        if ($data == 'undefined') return null;
        $query = "SELECT a.*
            FROM apidashboardquery a
            WHERE a.Oid='{$data}'";
        $data = $this->dbConnection->select(DB::raw($query))[0];
        $data->Action = $this->action();
        $data = collect($data);
        return $data;
    }

    public function destroy($data)
    {
        $query = "DELETE FROM apidashboardquery WHERE Oid='{$data}'";
        $data = $this->dbConnection->select(DB::delete($query));
    }

    public function listCharts() {
        return [
            [
                'Oid' => 'SquareArea',
                'Name' => 'Statistic Square',
                'ImageChartType' => 'http=>//public.ezbooking.co/chart/areasquare.PNG'
            ],
            [
                'Oid' => 'TitleArea',
                'Name' => 'Statistic Title',
                'ImageChartType' => 'http=>//public.ezbooking.co/chart/areasquare.PNG'
            ],
            [
                'Oid' => 'LandscapeArea',
                'Name' => 'Statistic Landscape',
                'ImageChartType' => 'http=>//public.ezbooking.co/chart/areasquare.PNG'
            ],
            [
                'Oid' => 'Pie',
                'Name' => 'Pie',
                'ImageChartType' => 'http=>//public.ezbooking.co/chart/pie.PNG'
            ],
            [
                'Oid' => 'Bar',
                'Name' => 'Bar',
                'ImageChartType' => 'http=>//public.ezbooking.co/chart/pie.PNG'
            ],
            [
                'Oid' => 'ListBulletin',
                'Name' => 'List (bulletin)',
                'ImageChartType' => 'http=>//public.ezbooking.co/chart/pie.PNG'
            ],
            [
                'Oid' => 'Timeline',
                'Name' => 'Timeline',
                'ImageChartType' => 'http=>//public.ezbooking.co/chart/pie.PNG'
            ],
            [
                'Oid' => 'Pie2',
                'Name' => 'Pie 2',
                'ImageChartType' => 'http=>//public.ezbooking.co/chart/pie.PNG'
            ],
            [
                'Oid' => 'Highlight',
                'Name' => 'Summary Highlight',
                'ImageChartType' => 'http=>//public.ezbooking.co/chart/pie.PNG'
            ],
            [
                'Oid' => 'PieMeter',
                'Name' => 'Pie Meter',
                'ImageChartType' => 'http=>//public.ezbooking.co/chart/pie.PNG'
            ],
            [
                'Oid' => 'SpeedMeter',
                'Name' => 'Speed Meter Dashboard',
                'ImageChartType' => 'http=>//public.ezbooking.co/chart/pie.PNG'
            ]
        ];
    }

    public function listIcons() {
        return [
            [ 'Oid' => 'activity', 'Name' => 'activity' ],
            [ 'Oid' => 'airplay', 'Name' => 'airplay' ],
            [ 'Oid' => 'alert-circle', 'Name' => 'alert-circle' ],
            [ 'Oid' => 'alert-octagon', 'Name' => 'alert-octagon' ],
            [ 'Oid' => 'alert-triangle', 'Name' => 'alert-triangle' ],
            [ 'Oid' => 'align-center', 'Name' => 'align-center' ],
            [ 'Oid' => 'align-justify', 'Name' => 'align-justify' ],
            [ 'Oid' => 'align-left', 'Name' => 'align-left' ],
            [ 'Oid' => 'align-right', 'Name' => 'align-right' ],
            [ 'Oid' => 'anchor', 'Name' => 'anchor' ],
            [ 'Oid' => 'aperture', 'Name' => 'aperture' ],
            [ 'Oid' => 'archive', 'Name' => 'archive' ],
            [ 'Oid' => 'arrow-down-circle', 'Name' => 'arrow-down-circle' ],
            [ 'Oid' => 'arrow-down-left', 'Name' => 'arrow-down-left' ],
            [ 'Oid' => 'arrow-down-right', 'Name' => 'arrow-down-right' ],
            [ 'Oid' => 'activity', 'Name' => 'activity' ],
            [ 'Oid' => 'airplay', 'Name' => 'airplay' ],
            [ 'Oid' => 'alert-circle', 'Name' => 'alert-circle' ],
            [ 'Oid' => 'alert-octagon', 'Name' => 'alert-octagon' ],
            [ 'Oid' => 'alert-triangle', 'Name' => 'alert-triangle' ],
            [ 'Oid' => 'align-center', 'Name' => 'align-center' ],
            [ 'Oid' => 'align-justify', 'Name' => 'align-justify' ],
            [ 'Oid' => 'align-left', 'Name' => 'align-left' ],
            [ 'Oid' => 'align-right', 'Name' => 'align-right' ],
            [ 'Oid' => 'anchor', 'Name' => 'anchor' ],
            [ 'Oid' => 'aperture', 'Name' => 'aperture' ],
            [ 'Oid' => 'archive', 'Name' => 'archive' ],
            [ 'Oid' => 'arrow-down-circle', 'Name' => 'arrow-down-circle' ],
            [ 'Oid' => 'arrow-down-left', 'Name' => 'arrow-down-left' ],
            [ 'Oid' => 'arrow-down-right', 'Name' => 'arrow-down-right' ],
            [ 'Oid' => 'arrow-down', 'Name' => 'arrow-down' ],
            [ 'Oid' => 'arrow-left-circle', 'Name' => 'arrow-left-circle' ],
            [ 'Oid' => 'arrow-left', 'Name' => 'arrow-left' ],
            [ 'Oid' => 'arrow-right-circle', 'Name' => 'arrow-right-circle' ],
            [ 'Oid' => 'arrow-right', 'Name' => 'arrow-right' ],
            [ 'Oid' => 'arrow-up-circle', 'Name' => 'arrow-up-circle' ],
            [ 'Oid' => 'arrow-up-left', 'Name' => 'arrow-up-left' ],
            [ 'Oid' => 'arrow-up-right', 'Name' => 'arrow-up-right' ],
            [ 'Oid' => 'arrow-up', 'Name' => 'arrow-up' ],
            [ 'Oid' => 'at-sign', 'Name' => 'at-sign' ],
            [ 'Oid' => 'award', 'Name' => 'award' ],
            [ 'Oid' => 'bar-chart-2', 'Name' => 'bar-chart-2' ],
            [ 'Oid' => 'bar-chart', 'Name' => 'bar-chart' ],
            [ 'Oid' => 'battery-charging', 'Name' => 'battery-charging' ],
            [ 'Oid' => 'battery', 'Name' => 'battery' ],
            [ 'Oid' => 'bell-off', 'Name' => 'bell-off' ],
            [ 'Oid' => 'bell', 'Name' => 'bell' ],
            [ 'Oid' => 'bluetooth', 'Name' => 'bluetooth' ],
            [ 'Oid' => 'bold', 'Name' => 'bold' ],
            [ 'Oid' => 'book-open', 'Name' => 'book-open' ],
            [ 'Oid' => 'book', 'Name' => 'book' ],
            [ 'Oid' => 'bookmark', 'Name' => 'bookmark' ],
            [ 'Oid' => 'box', 'Name' => 'box' ],
            [ 'Oid' => 'briefcase', 'Name' => 'briefcase' ],
            [ 'Oid' => 'calendar', 'Name' => 'calendar' ],
            [ 'Oid' => 'camera-off', 'Name' => 'camera-off' ],
            [ 'Oid' => 'camera', 'Name' => 'camera' ],
            [ 'Oid' => 'cast', 'Name' => 'cast' ],
            [ 'Oid' => 'check-circle', 'Name' => 'check-circle' ],
            [ 'Oid' => 'check-square', 'Name' => 'check-square' ],
            [ 'Oid' => 'camera-off', 'Name' => 'camera-off' ],
            [ 'Oid' => 'camera', 'Name' => 'camera' ],
            [ 'Oid' => 'cast', 'Name' => 'cast' ],
            [ 'Oid' => 'check-circle', 'Name' => 'check-circle' ],
            [ 'Oid' => 'check-square', 'Name' => 'check-square' ],
            [ 'Oid' => 'check', 'Name' => 'check' ],
            [ 'Oid' => 'chevron-down', 'Name' => 'chevron-down' ],
            [ 'Oid' => 'chevron-left', 'Name' => 'chevron-left' ],
            [ 'Oid' => 'chevron-right', 'Name' => 'chevron-right' ],
            [ 'Oid' => 'chevron-up', 'Name' => 'chevron-up' ],
            [ 'Oid' => 'chevrons-down', 'Name' => 'chevrons-down' ],
            [ 'Oid' => 'chevrons-left', 'Name' => 'chevrons-left' ],
            [ 'Oid' => 'chevrons-right', 'Name' => 'chevrons-right' ],
            [ 'Oid' => 'chevrons-up', 'Name' => 'chevrons-up' ],
            [ 'Oid' => 'chrome', 'Name' => 'chrome' ],
            [ 'Oid' => 'circle', 'Name' => 'circle' ],
            [ 'Oid' => 'clipboard', 'Name' => 'clipboard' ],
            [ 'Oid' => 'clock', 'Name' => 'clock' ],
            [ 'Oid' => 'cloud-drizzle', 'Name' => 'cloud-drizzle' ],
            [ 'Oid' => 'cloud-lightning', 'Name' => 'cloud-lightning' ],
            [ 'Oid' => 'cloud-off', 'Name' => 'cloud-off' ],
            [ 'Oid' => 'cloud-rain', 'Name' => 'cloud-rain' ],
            [ 'Oid' => 'cloud-snow', 'Name' => 'cloud-snow' ],
            [ 'Oid' => 'cloud', 'Name' => 'cloud' ],
            [ 'Oid' => 'code', 'Name' => 'code' ],
            [ 'Oid' => 'codepen', 'Name' => 'codepen' ],
            [ 'Oid' => 'codesandbox', 'Name' => 'codesandbox' ],
            [ 'Oid' => 'coffee', 'Name' => 'coffee' ],
            [ 'Oid' => 'columns', 'Name' => 'columns' ],
            [ 'Oid' => 'command', 'Name' => 'command' ],
            [ 'Oid' => 'compass', 'Name' => 'compass' ],
            [ 'Oid' => 'copy', 'Name' => 'copy' ],
            [ 'Oid' => 'corner-down-left', 'Name' => 'corner-down-left' ],
            [ 'Oid' => 'corner-down-right', 'Name' => 'corner-down-right' ],
            [ 'Oid' => 'corner-left-down', 'Name' => 'corner-left-down' ],
            [ 'Oid' => 'compass', 'Name' => 'compass' ],
            [ 'Oid' => 'copy', 'Name' => 'copy' ],
            [ 'Oid' => 'corner-down-left', 'Name' => 'corner-down-left' ],
            [ 'Oid' => 'corner-down-right', 'Name' => 'corner-down-right' ],
            [ 'Oid' => 'corner-left-down', 'Name' => 'corner-left-down' ],
            [ 'Oid' => 'corner-left-up', 'Name' => 'corner-left-up' ],
            [ 'Oid' => 'corner-right-down', 'Name' => 'corner-right-down' ],
            [ 'Oid' => 'corner-right-up', 'Name' => 'corner-right-up' ],
            [ 'Oid' => 'corner-up-left', 'Name' => 'corner-up-left' ],
            [ 'Oid' => 'corner-up-right', 'Name' => 'corner-up-right' ],
            [ 'Oid' => 'cpu', 'Name' => 'cpu' ],
            [ 'Oid' => 'credit-card', 'Name' => 'credit-card' ],
            [ 'Oid' => 'crop', 'Name' => 'crop' ],
            [ 'Oid' => 'crosshair', 'Name' => 'crosshair' ],
            [ 'Oid' => 'database', 'Name' => 'database' ],
            [ 'Oid' => 'delete', 'Name' => 'delete' ],
            [ 'Oid' => 'disc', 'Name' => 'disc' ],
            [ 'Oid' => 'dollar-sign', 'Name' => 'dollar-sign' ],
            [ 'Oid' => 'download-cloud', 'Name' => 'download-cloud' ],
            [ 'Oid' => 'download', 'Name' => 'download' ],
            [ 'Oid' => 'eye-off', 'Name' => 'eye-off' ],
            [ 'Oid' => 'eye', 'Name' => 'eye' ],
            [ 'Oid' => 'facebook', 'Name' => 'facebook' ],
            [ 'Oid' => 'fast-forward', 'Name' => 'fast-forward' ],
            [ 'Oid' => 'feather', 'Name' => 'feather' ],
            [ 'Oid' => 'figma', 'Name' => 'figma' ],
            [ 'Oid' => 'file-minus', 'Name' => 'file-minus' ],
            [ 'Oid' => 'file-plus', 'Name' => 'file-plus' ],
            [ 'Oid' => 'file-text', 'Name' => 'file-text' ],
            [ 'Oid' => 'file', 'Name' => 'file' ],
            [ 'Oid' => 'film', 'Name' => 'film' ],
            [ 'Oid' => 'filter', 'Name' => 'filter' ],
            [ 'Oid' => 'flag', 'Name' => 'flag' ],
            [ 'Oid' => 'folder-minus', 'Name' => 'folder-minus' ],
            [ 'Oid' => 'folder-plus', 'Name' => 'folder-plus' ],
            [ 'Oid' => 'folder', 'Name' => 'folder' ],
            [ 'Oid' => 'framer', 'Name' => 'framer' ],
            [ 'Oid' => 'frown', 'Name' => 'frown' ],
            [ 'Oid' => 'gift', 'Name' => 'gift' ],
            [ 'Oid' => 'git-branch', 'Name' => 'git-branch' ],
            [ 'Oid' => 'git-commit', 'Name' => 'git-commit' ],
            [ 'Oid' => 'git-merge', 'Name' => 'git-merge' ],
            [ 'Oid' => 'git-pull-request', 'Name' => 'git-pull-request' ],
            [ 'Oid' => 'github', 'Name' => 'github' ],
            [ 'Oid' => 'gitlab', 'Name' => 'gitlab' ],
            [ 'Oid' => 'globe', 'Name' => 'globe' ],
            [ 'Oid' => 'grid', 'Name' => 'grid' ],
            [ 'Oid' => 'hard-drive', 'Name' => 'hard-drive' ],
            [ 'Oid' => 'hash', 'Name' => 'hash' ],
            [ 'Oid' => 'headphones', 'Name' => 'headphones' ],
            [ 'Oid' => 'heart', 'Name' => 'heart' ],
            [ 'Oid' => 'help-circle', 'Name' => 'help-circle' ],
            [ 'Oid' => 'hexagon', 'Name' => 'hexagon' ],
            [ 'Oid' => 'home', 'Name' => 'home' ],
            [ 'Oid' => 'image', 'Name' => 'image' ],
            [ 'Oid' => 'inbox', 'Name' => 'inbox' ],
            [ 'Oid' => 'info', 'Name' => 'info' ],
            [ 'Oid' => 'instagram', 'Name' => 'instagram' ],
            [ 'Oid' => 'italic', 'Name' => 'italic' ],
            [ 'Oid' => 'key', 'Name' => 'key' ],
            [ 'Oid' => 'layers', 'Name' => 'layers' ],
            [ 'Oid' => 'layout', 'Name' => 'layout' ],
            [ 'Oid' => 'life-buoy', 'Name' => 'life-buoy' ],
            [ 'Oid' => 'link-2', 'Name' => 'link-2' ],
            [ 'Oid' => 'link', 'Name' => 'link' ],
            [ 'Oid' => 'linkedin', 'Name' => 'linkedin' ],
            [ 'Oid' => 'list', 'Name' => 'list' ],
            [ 'Oid' => 'loader', 'Name' => 'loader' ],
            [ 'Oid' => 'lock', 'Name' => 'lock' ],
            [ 'Oid' => 'log-in', 'Name' => 'log-in' ],
            [ 'Oid' => 'log-out', 'Name' => 'log-out' ],
            [ 'Oid' => 'mail', 'Name' => 'mail' ],
            [ 'Oid' => 'map-pin', 'Name' => 'map-pin' ],
            [ 'Oid' => 'map', 'Name' => 'map' ],
            [ 'Oid' => 'maximize-2', 'Name' => 'maximize-2' ],
            [ 'Oid' => 'maximize', 'Name' => 'maximize' ],
            [ 'Oid' => 'meh', 'Name' => 'meh' ],
            [ 'Oid' => 'menu', 'Name' => 'menu' ],
            [ 'Oid' => 'message-circle', 'Name' => 'message-circle' ],
            [ 'Oid' => 'message-square', 'Name' => 'message-square' ],
            [ 'Oid' => 'mic-off', 'Name' => 'mic-off' ],
            [ 'Oid' => 'mic', 'Name' => 'mic' ],
            [ 'Oid' => 'minimize-2', 'Name' => 'minimize-2' ],
            [ 'Oid' => 'minimize', 'Name' => 'minimize' ],
            [ 'Oid' => 'minus-circle', 'Name' => 'minus-circle' ],
            [ 'Oid' => 'minus-square', 'Name' => 'minus-square' ],
            [ 'Oid' => 'minus', 'Name' => 'minus' ],
            [ 'Oid' => 'monitor', 'Name' => 'monitor' ],
            [ 'Oid' => 'moon', 'Name' => 'moon' ],
            [ 'Oid' => 'more-horizontal', 'Name' => 'more-horizontal' ],
            [ 'Oid' => 'more-vertical', 'Name' => 'more-vertical' ],
            [ 'Oid' => 'mouse-pointer', 'Name' => 'mouse-pointer' ],
            [ 'Oid' => 'move', 'Name' => 'move' ],
            [ 'Oid' => 'music', 'Name' => 'music' ],
            [ 'Oid' => 'navigation-2', 'Name' => 'navigation-2' ],
            [ 'Oid' => 'navigation', 'Name' => 'navigation' ],
            [ 'Oid' => 'octagon', 'Name' => 'octagon' ],
            [ 'Oid' => 'package', 'Name' => 'package' ],
            [ 'Oid' => 'paperclip', 'Name' => 'paperclip' ],
            [ 'Oid' => 'pause-circle', 'Name' => 'pause-circle' ],
            [ 'Oid' => 'pause', 'Name' => 'pause' ],
            [ 'Oid' => 'pen-tool', 'Name' => 'pen-tool' ],
            [ 'Oid' => 'percent', 'Name' => 'percent' ],
            [ 'Oid' => 'phone-call', 'Name' => 'phone-call' ],
            [ 'Oid' => 'phone-forwarded', 'Name' => 'phone-forwarded' ],
            [ 'Oid' => 'phone-incoming', 'Name' => 'phone-incoming' ],
            [ 'Oid' => 'phone-missed', 'Name' => 'phone-missed' ],
            [ 'Oid' => 'phone-off', 'Name' => 'phone-off' ],
            [ 'Oid' => 'phone-outgoing', 'Name' => 'phone-outgoing' ],
            [ 'Oid' => 'phone', 'Name' => 'phone' ],
            [ 'Oid' => 'pie-chart', 'Name' => 'pie-chart' ],
            [ 'Oid' => 'play-circle', 'Name' => 'play-circle' ],
            [ 'Oid' => 'play', 'Name' => 'play' ],
            [ 'Oid' => 'plus-circle', 'Name' => 'plus-circle' ],
            [ 'Oid' => 'plus-square', 'Name' => 'plus-square' ],
            [ 'Oid' => 'plus', 'Name' => 'plus' ],
            [ 'Oid' => 'pocket', 'Name' => 'pocket' ],
            [ 'Oid' => 'power', 'Name' => 'power' ],
            [ 'Oid' => 'printer', 'Name' => 'printer' ],
            [ 'Oid' => 'radio', 'Name' => 'radio' ],
            [ 'Oid' => 'refresh-ccw', 'Name' => 'refresh-ccw' ],
            [ 'Oid' => 'refresh-cw', 'Name' => 'refresh-cw' ],
            [ 'Oid' => 'repeat', 'Name' => 'repeat' ],
            [ 'Oid' => 'rewind', 'Name' => 'rewind' ],
            [ 'Oid' => 'rotate-ccw', 'Name' => 'rotate-ccw' ],
            [ 'Oid' => 'rotate-cw', 'Name' => 'rotate-cw' ],
            [ 'Oid' => 'rss', 'Name' => 'rss' ],
            [ 'Oid' => 'save', 'Name' => 'save' ],
            [ 'Oid' => 'scissors', 'Name' => 'scissors' ],
            [ 'Oid' => 'search', 'Name' => 'search' ],
            [ 'Oid' => 'send', 'Name' => 'send' ],
            [ 'Oid' => 'server', 'Name' => 'server' ],
            [ 'Oid' => 'settings', 'Name' => 'settings' ],
            [ 'Oid' => 'share-2', 'Name' => 'share-2' ],
            [ 'Oid' => 'share', 'Name' => 'share' ],
            [ 'Oid' => 'shield-off', 'Name' => 'shield-off' ],
            [ 'Oid' => 'shield', 'Name' => 'shield' ],
            [ 'Oid' => 'shopping-bag', 'Name' => 'shopping-bag' ],
            [ 'Oid' => 'shopping-cart', 'Name' => 'shopping-cart' ],
            [ 'Oid' => 'shuffle', 'Name' => 'shuffle' ],
            [ 'Oid' => 'sidebar', 'Name' => 'sidebar' ],
            [ 'Oid' => 'skip-back', 'Name' => 'skip-back' ],
            [ 'Oid' => 'skip-forward', 'Name' => 'skip-forward' ],
            [ 'Oid' => 'slack', 'Name' => 'slack' ],
            [ 'Oid' => 'slash', 'Name' => 'slash' ],
            [ 'Oid' => 'sliders', 'Name' => 'sliders' ],
            [ 'Oid' => 'smartphone', 'Name' => 'smartphone' ],
            [ 'Oid' => 'smile', 'Name' => 'smile' ],
            [ 'Oid' => 'speaker', 'Name' => 'speaker' ],
            [ 'Oid' => 'square', 'Name' => 'square' ],
            [ 'Oid' => 'star', 'Name' => 'star' ],
            [ 'Oid' => 'stop-circle', 'Name' => 'stop-circle' ],
            [ 'Oid' => 'sun', 'Name' => 'sun' ],
            [ 'Oid' => 'sunrise', 'Name' => 'sunrise' ],
            [ 'Oid' => 'sunset', 'Name' => 'sunset' ],
            [ 'Oid' => 'tablet', 'Name' => 'tablet' ],
            [ 'Oid' => 'tag', 'Name' => 'tag' ],
            [ 'Oid' => 'target', 'Name' => 'target' ],
            [ 'Oid' => 'terminal', 'Name' => 'terminal' ],
            [ 'Oid' => 'thermometer', 'Name' => 'thermometer' ],
            [ 'Oid' => 'thumbs-down', 'Name' => 'thumbs-down' ],
            [ 'Oid' => 'thumbs-up', 'Name' => 'thumbs-up' ],
            [ 'Oid' => 'toggle-left', 'Name' => 'toggle-left' ],
            [ 'Oid' => 'toggle-right', 'Name' => 'toggle-right' ],
            [ 'Oid' => 'tool', 'Name' => 'tool' ],
            [ 'Oid' => 'trash-2', 'Name' => 'trash-2' ],
            [ 'Oid' => 'trash', 'Name' => 'trash' ],
            [ 'Oid' => 'trello', 'Name' => 'trello' ],
            [ 'Oid' => 'trending-down', 'Name' => 'trending-down' ],
            [ 'Oid' => 'trending-up', 'Name' => 'trending-up' ],
            [ 'Oid' => 'triangle', 'Name' => 'triangle' ],
            [ 'Oid' => 'truck', 'Name' => 'truck' ],
            [ 'Oid' => 'tv', 'Name' => 'tv' ],
            [ 'Oid' => 'twitch', 'Name' => 'twitch' ],
            [ 'Oid' => 'twitter', 'Name' => 'twitter' ],
            [ 'Oid' => 'type', 'Name' => 'type' ],
            [ 'Oid' => 'umbrella', 'Name' => 'umbrella' ],
            [ 'Oid' => 'underline', 'Name' => 'underline' ],
            [ 'Oid' => 'unlock', 'Name' => 'unlock' ],
            [ 'Oid' => 'upload-cloud', 'Name' => 'upload-cloud' ],
            [ 'Oid' => 'upload', 'Name' => 'upload' ],
            [ 'Oid' => 'user-check', 'Name' => 'user-check' ],
            [ 'Oid' => 'user-minus', 'Name' => 'user-minus' ],
            [ 'Oid' => 'user-plus', 'Name' => 'user-plus' ],
            [ 'Oid' => 'user-x', 'Name' => 'user-x' ],
            [ 'Oid' => 'user', 'Name' => 'user' ],
            [ 'Oid' => 'users', 'Name' => 'users' ],
            [ 'Oid' => 'video-off', 'Name' => 'video-off' ],
            [ 'Oid' => 'video', 'Name' => 'video' ],
            [ 'Oid' => 'voicemail', 'Name' => 'voicemail' ],
            [ 'Oid' => 'volume-1', 'Name' => 'volume-1' ],
            [ 'Oid' => 'volume-2', 'Name' => 'volume-2' ],
            [ 'Oid' => 'volume-x', 'Name' => 'volume-x' ],
            [ 'Oid' => 'volume', 'Name' => 'volume' ],
            [ 'Oid' => 'watch', 'Name' => 'watch' ],
            [ 'Oid' => 'wifi-off', 'Name' => 'wifi-off' ],
            [ 'Oid' => 'wifi', 'Name' => 'wifi' ],
            [ 'Oid' => 'wind', 'Name' => 'wind' ],
            [ 'Oid' => 'x-circle', 'Name' => 'x-circle' ],
            [ 'Oid' => 'x-octagon', 'Name' => 'x-octagon' ],
            [ 'Oid' => 'x-square', 'Name' => 'x-square' ],
            [ 'Oid' => 'x', 'Name' => 'x' ],
            [ 'Oid' => 'youtube', 'Name' => 'youtube' ],
            [ 'Oid' => 'zap-off', 'Name' => 'zap-off' ],
            [ 'Oid' => 'zap', 'Name' => 'zap' ],
            [ 'Oid' => 'zoom-in', 'Name' => 'zoom-in' ],
            [ 'Oid' => 'zoom-out', 'Name' => 'zoom-out' ]
        ];
    }
}
