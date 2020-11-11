<?php

namespace App\Core\Security\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Illuminate\Support\Facades\DB;
use App\Core\Security\Entities\User;
use App\Core\Master\Entities\Currency;
use App\Core\Master\Entities\Company;
use App\Core\Master\Entities\BusinessPartner;
use Illuminate\Support\Facades\Hash;

class CreateUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    /**
     * Create a new job instance.
     * @param array $data
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user;
        DB::transaction(function() use (&$user) {
            if (!isset($this->data['Company'])) $this->data['Company'] = config('app.company_id');
            $company = Company::find($this->data['Company']);
            if (!isset($this->data['BusinessPartner'])) $this->data['BusinessPartner'] = $company->CustomerCash;
            $businessPartner = BusinessPartner::find($this->data['BusinessPartner']);
            if (!isset($this->data['Currency'])) $this->data['Currency'] = $businessPartner ? $businessPartner->SalesCurrency : $company->Currency;
            // if (!isset($this->data['Currency'])) $this->data['Currency'] = $company->Currency;
            if (!isset($this->data['Lang'])) $this->data['Lang'] = $company->Lang;
            $this->data['StoredPassword2'] = $this->data['Password'];
            $this->data['IsActive'] = true;
            $this->data['PaymentCode'] = rand(100000,999999);
            unset($this->data['Password']);
            $user = User::create($this->data);
        });
        return $user;
    }
}
