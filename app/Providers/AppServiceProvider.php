<?php

namespace App\Providers;

use App\Services\WhatsApp\PengirimPesan;
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
        // Ganti dengan driver sungguhan (Fonnte/WA Cloud API) saat sudah ada kredensial.
        $this->app->bind(PengirimPesan::class, PengirimPesanLog::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Pengaturan::lupakanSemua();
    }
}
