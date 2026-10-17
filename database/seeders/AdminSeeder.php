<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@angkringan.hotber'],
            [
                'nama' => 'Admin Hotber',
                'password' => env('ADMIN_PASSWORD', 'hot*8Er'),
            ]
        );
    }
}
