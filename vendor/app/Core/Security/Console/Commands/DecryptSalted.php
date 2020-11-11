<?php

namespace App\Core\Security\Console\Commands;

use Illuminate\Console\Command;
use App\Core\Ethereum\Entities\ETHWalletAddress;
use App\Core\Security\Entities\User;
use App\Core\Master\Entities\Company;
use App\Core\POS\Entities\WalletBalance;

class DecryptSalted extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:decrypt-salted {input}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt salted';

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
        $this->info(decrypt_salted($this->argument('input')));
    }
}
