<?php

namespace App\Providers;

use App\Services\PackagePricingService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            $this->app->make(PackagePricingService::class)->applyToConfig();
        } catch (\Throwable) {
            // settings table may not exist yet during migrate
        }

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            $view->with([
                'userName' => $user?->madrasah_name ?? $user?->name,
                'userEmail' => $user?->email,
            ]);
        });
    }
}
