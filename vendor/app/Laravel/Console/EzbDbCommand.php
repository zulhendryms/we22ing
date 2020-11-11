<?php

namespace App\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class EzbDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ezb:db {connection} {--c|command=} {--d|directory=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $databases;

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
        $this->databases =  [
            [
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'hotel_v1_db',
                'username' => 'hotel_v1_user',
                'password' => 'hotel_v1_pass',
            ],
            [
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'pivottable_v1_db',
                'username' => 'pivottable_v1_user',
                'password' => 'pivottable_v1_pass',
            ],
        ];
        $this->connectionKey = 'mysql';

        if ($this->argument('connection') !== 'all') {
            if (! is_numeric($this->argument('connection'))) {
                $this->error('Invalid connection index given! "'.$this->argument('connection').'"');
                $this->error('Connection should be in integer value starting from 0 (index of connection) or "all"');
                return;
            }
            $index = (int) $this->argument('connection');
            if (! array_key_exists($index, $this->databases)) {
                $this->error('Invalid connection index given! "'.$this->argument('connection').'"');
                return;
            }
            $this->databases = $this->databases[$index];
        }

        $migrationsRelativePath = DIRECTORY_SEPARATOR .'database' . DIRECTORY_SEPARATOR . 'migrations';

        $directory = $this->option('directory');

        if ($directory) {
            $migrationPathFromAppRoot = $migrationsRelativePath . DIRECTORY_SEPARATOR . $directory;
            $this->line('Starting Migration: in "' . $migrationPathFromAppRoot . '"');
            $this->migrateEachDb($migrationPathFromAppRoot);
            $this->line('Migration Complete: in "' . $migrationPathFromAppRoot . '"');
        }
        else {
            $migrationSubDirectories = glob(base_path($migrationsRelativePath) . DIRECTORY_SEPARATOR . '*' , GLOB_ONLYDIR);
            foreach ($migrationSubDirectories as $migrationPathAbsolute) {
                $migrationPathFromAppRoot = $migrationsRelativePath . DIRECTORY_SEPARATOR . basename($migrationPathAbsolute);
                $this->line('Starting Migration: in "' . $migrationPathFromAppRoot . '"');
                $this->migrateEachDb($migrationPathFromAppRoot);
                $this->line('Migration Complete: in "' . $migrationPathFromAppRoot . '"');
            }
        }
    }

    public function migrateEachDb(string $path) {
        foreach ($this->databases as $database)
        {
            $this->line('Starting Migration: for database "'.$database['database'].'"'.' at host "'.$database['host']);

            Config::set('database.connections.mysql.host', $database['host']);
            Config::set('database.connections.mysql.port', $database['port']);
            Config::set('database.connections.mysql.database', $database['database']);
            Config::set('database.connections.mysql.username', $database['username']);
            Config::set('database.connections.mysql.password', $database['password']);
            DB::purge($this->connectionKey);
            DB::reconnect($this->connectionKey);
            
            $this->call($this->option('command'), array('--database' => $this->connectionKey, '--path' => $path));

            $this->line('Migration Complete: for database "'.$database['database'].'"'.' at host "'.$database['host']);
        }
    }
}
