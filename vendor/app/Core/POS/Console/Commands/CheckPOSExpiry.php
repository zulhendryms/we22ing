<?php

namespace App\Core\POS\Console\Commands;

use Illuminate\Console\Command;
use App\Core\POS\Entities\PointOfSale;

class CheckPOSExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:check-pos-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check expired transaction';

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
        $pos = PointOfSale::whereRaw('DateExpiry < now()')->whereHas('StatusObj', function ($query) {
            $query->whereIn('Code', [ 'entry', 'ordered', 'verify' ]);
        })->get();

        if ($pos->count() > 0) {
            $service = new \App\Core\POS\Services\POSStatusService();
            foreach ($pos as $p) {
                $service->checkExpiry($p);
            }
        }
    }
}
