<?php

namespace App\Providers;

use App\Models\Company;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', static function (\Illuminate\Contracts\View\View $view): void {
            $route = request()->route();
            $company = $route?->parameter('company');
            $view->with('navCompany', $company instanceof Company ? $company : null);
        });
    }
}
