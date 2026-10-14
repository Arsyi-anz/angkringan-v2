<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Kunjungan;
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
                    && (int) $row->visits_90d_count === 1
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
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_id');
    }
}
