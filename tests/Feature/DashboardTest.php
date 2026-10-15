<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Hampers;
use App\Models\Kunjungan;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): Admin
    {
        return Admin::create([
            'nama' => 'Admin Uji',
            'email' => 'admin@example.test',
            'password' => 'rahasia123',
        ]);
    }

    /**
     * Customer yang berulang tahun hari ini dengan sejumlah kunjungan
     * dalam jendela evaluasi (tanggal lahir - window_days).
     */
    private function birthdayCustomer(Admin $admin, string $nama, int $visits, array $attributes = []): Customer
    {
        $customer = Customer::create(array_merge([
            'nama' => $nama,
            'no_wa' => '628511111111',
            'lahir_tgl' => today()->day,
            'lahir_bln' => today()->month,
            'setuju_pesan' => true,
        ], $attributes));

        for ($i = 1; $i <= $visits; $i++) {
            Kunjungan::create([
                'id_customer' => $customer->id_customer,
                'tgl_kunjungan' => today()->subDays($i)->toDateString(),
                'id_admin' => $admin->id_admin,
            ]);
        }

        return $customer;
    }

    public function test_dashboard_is_rendered_with_summary_data(): void
    {
        $admin = $this->makeAdmin();

        $this->birthdayCustomer($admin, 'Andi', 5);
        Customer::create(['nama' => 'Budi', 'no_wa' => '628522222222']);

        Voucher::create(['kode_voucher' => 'IG-AAA111', 'kategori' => 'IG', 'status' => 'AVAILABLE']);
        Voucher::create(['kode_voucher' => 'IG-BBB222', 'kategori' => 'IG', 'status' => 'USED']);
        Voucher::create(['kode_voucher' => 'BRO-CCC333', 'kategori' => 'BRO', 'status' => 'USED']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('totalCustomers', 2)
            ->assertViewHas('withBirthDate', 1)
            ->assertViewHas('withConsent', 1)
            ->assertViewHas('vouchersCreated', 3)
            ->assertViewHas('vouchersUsed', 2)
            ->assertViewHas('unusedVouchers', 1)
            ->assertViewHas('hampersEligible', 1)
            ->assertViewHas('latestCustomers', fn ($customers) => $customers->count() === 2)
            ->assertViewHas('voucherSources', fn ($sources) => $sources->pluck('count', 'code')->all() == ['IG' => 2, 'BRO' => 1]);
    }

    public function test_customer_with_birthdate_who_already_claimed_hampers_is_not_eligible(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->birthdayCustomer($admin, 'Citra', 5, ['setuju_pesan' => false]);

        $idPesan = DB::table('pesan_ulang_tahun')->insertGetId([
            'id_customer' => $customer->id_customer,
            'tahun' => now()->year,
            'jenis' => 'HAMPERS',
            'status' => 'TERKIRIM',
        ]);

        Hampers::create([
            'id_customer' => $customer->id_customer,
            'id_pesan' => $idPesan,
            'tahun_kuota' => now()->year,
            'kode_klaim' => 'HAM-ABC123',
            'batas_ambil' => now()->addDays(14)->toDateString(),
            'status' => 'BELUM_DIAMBIL',
        ]);

        $this->assertFalse($customer->fresh()->hampers_eligible);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('hampersEligible', 0);
    }
}
