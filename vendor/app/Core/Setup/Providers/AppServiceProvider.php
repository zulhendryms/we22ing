<?php

namespace App\Core\Setup\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('complex_password', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $value);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('core.external.authy', function ($app) {
            return new \Authy\AuthyApi(config('services.authy.api_key'));
        });
    }
}
