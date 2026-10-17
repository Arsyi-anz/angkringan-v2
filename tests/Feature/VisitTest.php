<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Kunjungan;
use App\Support\Pengaturan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitTest extends TestCase
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

    private function makeCustomer(string $nama = 'Budi', string $noWa = '6281234567890'): Customer
    {
        return Customer::create(['nama' => $nama, 'no_wa' => $noWa]);
    }

    public function test_guest_is_redirected_from_visits_page(): void
    {
        $this->get('/admin/visits')->assertRedirect(route('login'));
    }

    public function test_visits_page_is_rendered_with_customer_stats(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();
        // Tanggal lahir = hari ini supaya kunjungan hari ini berada di dalam jendela [ulang tahun - 90, ulang tahun].
        $customer->update([
            'lahir_tgl' => (int) now()->day,
            'lahir_bln' => (int) now()->month,
        ]);

        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => now()->toDateString(),
            'id_admin' => $admin->id_admin,
        ]);

        $this->actingAs($admin, 'admin');

        $this->get('/admin/visits')
            ->assertOk()
            ->assertSee('Kunjungan Dine-in')
            ->assertSee('Budi')
            ->assertSee('Dalam 90 hari')
            ->assertSee('data-ang-modal="ang-visit-modal"', false)
            ->assertSee('id="ang-visit-modal"', false)
            ->assertViewHas('customers', function ($customers) {
                $row = $customers->firstWhere('nama', 'Budi');

                return $row !== null
                    && (int) $row->visits_count === 1
                    && (int) $row->visits_window_count === 1
                    && $row->last_visit_at !== null;
            });
    }

    public function test_visits_page_only_includes_modal_once(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $html = $this->get('/admin/visits')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="ang-visit-modal"'));
    }

    public function test_visit_can_be_recorded_and_redirects_back(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();
        $today = now()->toDateString();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.visits.index'))
            ->post(route('admin.visits.store'), [
                '_ang_form' => 'visit',
                'customer_id' => $customer->id_customer,
                'visited_at' => $today,
                'visit_type' => 'dine_in',
            ])
            ->assertRedirect(route('admin.visits.index'));

        $this->assertDatabaseHas('kunjungan', [
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => $today,
            'id_admin' => $admin->id_admin,
        ]);

        $this->assertEquals('Kunjungan dine-in berhasil dicatat.', session('status'));
    }

    public function test_visit_can_be_recorded_from_customer_page_and_returns_there(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.customers.index'))
            ->post(route('admin.visits.store'), [
                'customer_id' => $customer->id_customer,
                'visited_at' => now()->toDateString(),
                'visit_type' => 'dine_in',
            ])
            ->assertRedirect(route('admin.customers.index'));
    }

    public function test_same_customer_cannot_be_recorded_twice_on_the_same_day(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();
        $today = now()->toDateString();

        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => $today,
            'id_admin' => $admin->id_admin,
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.visits.store'), [
                'customer_id' => $customer->id_customer,
                'visited_at' => $today,
                'visit_type' => 'dine_in',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_id');
    }

    public function test_future_visit_date_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.visits.store'), [
                'customer_id' => $customer->id_customer,
                'visited_at' => now()->addDay()->toDateString(),
                'visit_type' => 'dine_in',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('visited_at');
    }

    public function test_take_away_visit_type_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.visits.store'), [
                'customer_id' => $customer->id_customer,
                'visited_at' => now()->toDateString(),
                'visit_type' => 'take_away',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('visit_type');
    }

    public function test_unknown_customer_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->postJson(route('admin.visits.store'), [
            'customer_id' => 99999,
            'visited_at' => now()->toDateString(),
            'visit_type' => 'dine_in',
        ])->assertStatus(422)->assertJsonValidationErrors('customer_id');
    }

    public function test_visits_page_can_be_sorted_by_total_visits(): void
    {
        $admin = $this->makeAdmin();
        $sedikit = $this->makeCustomer('Andi', '628111111111');
        $banyak = $this->makeCustomer('Budi', '628222222222');

        foreach (['2026-01-01', '2026-02-01'] as $tgl) {
            Kunjungan::create([
                'id_customer' => $banyak->id_customer,
                'tgl_kunjungan' => $tgl,
                'id_admin' => $admin->id_admin,
            ]);
        }
        Kunjungan::create([
            'id_customer' => $sedikit->id_customer,
            'tgl_kunjungan' => '2026-03-01',
            'id_admin' => $admin->id_admin,
        ]);

        $this->actingAs($admin, 'admin');

        $this->get('/admin/visits?sort=visits_desc')
            ->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->pluck('nama')->all() === ['Budi', 'Andi']);

        $this->get('/admin/visits?sort=visits_asc')
            ->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->pluck('nama')->all() === ['Andi', 'Budi']);

        // Tanpa sort: urut kunjungan terakhir (Andi 1 Mar > Budi 1 Feb).
        $this->get('/admin/visits')
            ->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->pluck('nama')->all() === ['Andi', 'Budi']);
    }

    public function test_window_column_counts_visits_before_birthday_and_title_follows_setting(): void
    {
        $admin = $this->makeAdmin();
        Pengaturan::simpan('window_days', '30');

        // Ulang tahun 20 hari lagi (masih di jendela), kunjungan terjadi 5 hari lalu.
        $ulangTahun = today()->addDays(20);
        $customer = $this->makeCustomer('Citra', '628333333333');
        $customer->update([
            'lahir_tgl' => (int) $ulangTahun->day,
            'lahir_bln' => (int) $ulangTahun->month,
        ]);
        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => now()->subDays(5)->toDateString(),
            'id_admin' => $admin->id_admin,
        ]);

        // Ulang tahun 50 hari lagi (di luar jendela 30 hari), tapi pernah kunjungan 10 hari lalu.
        $jauh = $this->makeCustomer('Dodi', '628444444444');
        $jauh->update([
            'lahir_tgl' => (int) today()->addDays(50)->day,
            'lahir_bln' => (int) today()->addDays(50)->month,
        ]);
        Kunjungan::create([
            'id_customer' => $jauh->id_customer,
            'tgl_kunjungan' => now()->subDays(10)->toDateString(),
            'id_admin' => $admin->id_admin,
        ]);

        $this->actingAs($admin, 'admin');

        $this->get('/admin/visits')
            ->assertOk()
            ->assertSee('Dalam 30 hari')
            ->assertViewHas('windowDays', 30)
            ->assertViewHas('customers', function ($customers) {
                $citra = $customers->firstWhere('nama', 'Citra');
                $dodi = $customers->firstWhere('nama', 'Dodi');

                // Citra: kunjungan masih dalam [ulang tahun - 30, ulang tahun] -> 1.
                // Dodi: jendela [ulang tahun-30 .. hari ini] belum dimulai (ulang tahun 50 hari lagi) -> 0.
                return $citra !== null
                    && (int) $citra->visits_window_count === 1
                    && $dodi !== null
                    && (int) $dodi->visits_window_count === 0
                    && (int) $dodi->visits_count === 1;
            });
    }
}
