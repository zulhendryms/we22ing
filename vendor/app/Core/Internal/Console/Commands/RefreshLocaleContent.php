<?php

namespace App\Core\Internal\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshLocaleContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:refresh-locale-content';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh localization files';

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
        (new \App\Core\Internal\Services\LocaleContentService())->generate();
        Artisan::call('view:clear');
    }
}
