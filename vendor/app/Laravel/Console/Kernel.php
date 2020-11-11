<?php

namespace App\Laravel\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Core
        \App\Core\Security\Console\Commands\EncryptSalted::class,
        \App\Core\Security\Console\Commands\DecryptSalted::class,
        \App\Core\Internal\Console\Commands\RefreshLocaleContent::class,
        \App\Core\Internal\Console\Commands\RefreshAutoNumberConfig::class,
        \App\Core\POS\Console\Commands\CheckPOSExpiry::class,
        // \App\Core\Chat\Console\Commands\StartChatServer::class,
        \App\Core\Travel\Console\Commands\CheckAllotmentCutoffDay::class,

        \App\Laravel\Console\EzbDbCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
