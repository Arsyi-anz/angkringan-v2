<?php

namespace App\Providers;

use App\Services\WhatsApp\PengirimPesan;
use App\Services\WhatsApp\PengirimPesanCloudApi;
use App\Services\WhatsApp\PengirimPesanLog;
use App\Support\Pengaturan;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PengirimPesan::class, function () {
            return config('whatsapp.driver') === 'cloud'
                ? new PengirimPesanCloudApi
                : new PengirimPesanLog;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Pengaturan::lupakanSemua();
    }
}
