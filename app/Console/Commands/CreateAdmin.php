<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {nama} {email} {--password=}';

    protected $description = 'Membuat atau memperbarui akun admin';

    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Password admin');

        if (blank($password)) {
            $this->error('Password wajib diisi.');

            return self::FAILURE;
        }

        $admin = Admin::updateOrCreate(
            ['email' => $this->argument('email')],
            [
                'nama' => $this->argument('nama'),
                'password' => Hash::make($password),
            ]
        );

        $this->info("Admin {$admin->email} berhasil disimpan.");

        return self::SUCCESS;
    }
}
