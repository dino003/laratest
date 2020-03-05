<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Factory;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Factory $factory): void
    {
        $factory->addExtension('yml', 'file');
    }
}
