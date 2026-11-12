<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Berjalan tiap menit sepanjang hari: begitu ada member berulang
        // tahun hari itu yang memenuhi syarat, pesan langsung dikirim
        // (tidak menunggu jadwal pagi). Aman karena record TERKIRIM
        // di-skip oleh command, dan proses dicegah tumpang tindih.
        $schedule->command('birthday:send')
            ->everyMinute()
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
