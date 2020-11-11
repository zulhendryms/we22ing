<?php

namespace App\Core\Security\Providers;

use Laravel\Passport\Bridge\AccessTokenRepository as PassportTokenRepository;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Core\Security\Bridge\AccessTokenRepository;

class PassportServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(PassportTokenRepository::class, AccessTokenRepository::class); 
    }
}
