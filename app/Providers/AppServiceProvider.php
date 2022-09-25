<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Carbon::setWeekStartsAt(Carbon::MONDAY);

        Validator::extend('phone_number', function ($attribute, $value, $parameters) {
            return (substr($value, 0, 1) == '+' || substr($value, 0, 1) == '0') && is_numeric($value);
        });
    }
}
