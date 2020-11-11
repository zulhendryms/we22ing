<?php

namespace App\Core\Internal\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshAutoNumberConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:refresh-autonumber-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh autonumber config';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = base_path("app/Core");
        $dirs = scandir(base_path("app/Core"));
        array_splice($dirs, 0,  2);

        $result = '<?php'.PHP_EOL.'return ['.PHP_EOL;

        foreach ($dirs as $dir) {
            $entitiesPath = $path.'/'.$dir."/Entities";
            $this->info($entitiesPath);
            if (!is_dir($entitiesPath)) continue;
            $entities = scandir($entitiesPath);
            array_splice($entities, 0,  2);
            foreach ($entities as $entity) {
                $entity = str_replace('.php', '', $entity);
                if ($entity == "BaseModel") continue;
                $class = "\\App\\Core\\{$dir}\\Entities\\{$entity}";
                $tableName = (new $class)->getTable();
                if (empty($tableName)) continue;
                $result .= "  '{$tableName}' => 'App\\Core\\".$dir."\\Entities\\".$entity."',".PHP_EOL;
            }
        }

        $result .= '];';

        $filename = base_path('config/autonumber.php');
        $file = fopen($filename, 'w');
        fwrite($file, $result);
        fclose($file);
        try {
            chmod($filename, 0777);
        } catch (\Exception $ex) { }
    }
}
