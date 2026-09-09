<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
        // Nama hari & bulan tampil dalam bahasa Indonesia.
        Carbon::setLocale('id');

        // Pagination memakai tampilan bawaan sistem (bukan Tailwind default).
        Paginator::defaultView('vendor.pagination.siv');
        Paginator::defaultSimpleView('vendor.pagination.siv');

        // Hormati skema HTTPS bila aplikasi di-deploy di belakang proxy TLS.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
