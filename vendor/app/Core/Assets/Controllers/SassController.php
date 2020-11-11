<?php

namespace App\Core\Assets\Controllers;

use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use Leafo\ScssPhp\Compiler;

class SassController extends Controller 
{

    protected $path;
    protected $debug;
    protected $theme;

    public function __construct()
    {
        $this->debug = config('app.debug');
        $this->path = config('core.sass.path');
        $this->theme = 'variables';
        if (config('app.theme') != 'default') $this->theme .= '.'.config('app.theme');
    }

    /**
     * Compile a .scss file
     * 
     * @param Request $request
     * @param string $name
     */
    public function index(Request $request, $name)
    {
        $compiledPath = public_path('css');
        $compiledFile = $compiledPath.'/'.$name.'.'.config('app.theme').'.css';
        $output = '';

        if (!$this->debug && is_file($compiledFile)) {
            $output = file_get_contents($compiledFile);
        } else {
            $rawFile = $this->path.'/'.$name.'.scss';
            $themeFile = $this->path.'/_'.$this->theme.'.scss';
            if (!is_file($rawFile)) {
                return response('File not found', 404);
            }

            if (is_file($compiledFile)) {
                $shouldCompile = is_file($themeFile) && filemtime($themeFile) > filemtime($compiledFile);
                if (!$shouldCompile) $shouldCompile = filemtime($rawFile) > filemtime($compiledFile);
                if (!$shouldCompile)  $output = file_get_contents($compiledFile);
            }

            if (empty($output)) {
                if (!is_dir($compiledPath)) {
                    mkdir($compiledPath);
                }
                $output = $this->compile($rawFile);
                $this->saveAsFile($compiledFile, $output);
            }
        }
        return response(
            $output,
            200,
            [ 'Content-Type' => 'text/css' ]
        );
    }

    /**
     * Save output to .css file
     * 
     * @param string $filename
     * @param string $output
     */
    protected function saveAsFile($filename, $output)
    {
        $file = fopen($filename, 'w');
        fwrite($file, $output);
        fclose($file);
        try {
            chmod($filename, 0777);
        } catch (\Exception $ex) { }
    }

    protected function compile($file)
    {
        $content = file_get_contents($file);
        if (is_file($this->path.'/_'.$this->theme.'.scss')) {
            $content = str_replace('variables', $this->theme, $content);
        }
        $scss = new Compiler();
        $scss->setImportPaths([
            base_path('node_modules'),
            $this->path,
        ]);
        if (!$this->debug) {
            $scss->setFormatter('Leafo\ScssPhp\Formatter\Compact');
        }
        $output = $scss->compile($content);
        return $output;
    }
}