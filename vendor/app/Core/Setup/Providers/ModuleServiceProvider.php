<?php 
namespace App\Core\Setup\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;

class ModuleServiceProvider extends RouteServiceProvider {

    protected $ignore = [
        'Laravel'
    ];

    protected $ignoreSub = [
        'Setup', 'Helpers', 'Constants'
    ];

    public function map() 
    {
        $modules = config('modules');

        foreach ($modules as $key => $module) {
            if (is_array($module)) {
                $dir = $key;
            } else {
                $dir = $module;
            }
            $path = base_path("app/{$dir}");
            if (!is_dir($path)) continue;

            $web = []; $api = [];

            $subs = scandir($path);
            array_splice($subs, 0,  2);

            foreach ($subs as $sub) {
                $subpath = $path."/{$sub}";
                if (!is_dir($subpath)) continue;
                if (file_exists($subpath."/web.php")) $web[] = $sub;
                if (file_exists($subpath."/api.php")) $api[] = $sub;
                if (is_dir($subpath."/Views")) $this->loadViewsFrom($subpath."/Views", "${dir}\\{$sub}");
            }

            if (!empty($web)) {
                $this->loadModuleRoutes($dir, $web, 'web');
            }

            if (!empty($api)) {
                $this->loadModuleRoutes($dir, $api, 'api');
            }
        }
    }

    protected function loadModuleRoutes($module, $subs, $type)
    {
        $middlewares = [
            $type,
            'access',
            'config',
        ];
        $prefix = '';
        $config = config("modules.".$module);
        if (is_array($config) && isset($config[$type])) {
            $config = $config[$type];
            if (isset($config['middleware'])) $middlewares = array_merge($middlewares, $config['middleware']);
            // Use this to allow override existing $middleware
            // if (isset($config['middleware'])) $middlewares = array_reverse(array_unique(array_reverse(array_merge($middlewares, $config['middleware']))));
            if (isset($config['prefix'])) {
                if (!starts_with($config['prefix'], '/')) $prefix .='/'.$config['prefix'];
                else $prefix .= $config['prefix'];
            }
        }

        if ($type == 'api' && $prefix == strtolower($module)) $prefix .= "/api";

        Route::middleware($middlewares)
        ->prefix($prefix)
        ->group(function () use ($module, $subs, $type) {
            foreach ($subs as $sub) {
                $namespace = "App\\{$module}\\{$sub}\\Controllers";
                $path = base_path("app/{$module}/{$sub}");
                if (is_dir($path."/Controllers/".ucfirst($type))) {
                    $namespace = "App\\{$module}\\{$sub}\\Controllers\\".ucfirst($type);
                }
                Route::namespace($namespace)
                ->group(function () use ($path, $type) {
                    require $path."/{$type}.php";
                });
            }
        });
    }
}