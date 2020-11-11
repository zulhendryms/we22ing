<?php

namespace App\Core\Internal\Services;

use App\Core\Master\Entities\LocaleContent;

class LocaleContentService 
{
    public function generate()
    {
        $langs = ['en', 'zh'];
        $groups = LocaleContent::select('Group')->distinct()->pluck('Group');
        $results = [];

        foreach ($langs as $lang) {
            $path = base_path().'/resources/lang/'.$lang;
            if (!file_exists($path)) mkdir($path);
            try {
                chmod($path, 0777);
            } catch (\Exception $ex) { }
        }

        foreach ($groups as $group) {
            foreach ($langs as $lang) {
                $path = base_path().'/resources/lang/'.$lang.'/'.$group.'.php';
                if (file_exists($path)) unlink($path);
                $results[$lang] = '<?php'.PHP_EOL.'return ['.PHP_EOL;
            }
            $contents = LocaleContent::where('Group', $group)->get();
            foreach ($contents as $content) {
                foreach ($langs as $lang) {
                    if (isset($content->{strtoupper($lang)})) {
                        $results[$lang] .= "    '".$content->Key."' => '".addslashes($content->{strtoupper($lang)})."',".PHP_EOL;
                    }
                }
            }
            foreach ($langs as $lang) {
                $results[$lang] .= '];';
                $dirname = base_path().'/resources/lang/'.$lang;
                $filename = $dirname.'/'.$group.'.php';
                $file = fopen($filename, 'w');
                fwrite($file, $results[$lang]);
                fclose($file);
                try {
                    chmod($filename, 0777);
                } catch (\Exception $ex) { }
            }
        }
    }
}