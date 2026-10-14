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

    public function test_visit_can_be_recorded(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin, 'admin');
        $customer = Customer::create(['nama' => 'Budi', 'no_wa' => '6281234567890']);

        $this->post('/admin/visits', [
            'customer_id' => $customer->id_customer,
            'visited_at' => '2026-01-05',
            'visit_type' => 'dine_in',
        ])->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('kunjungan', [
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => '2026-01-05',
            'id_admin' => $admin->id_admin,
        ]);

        $this->assertEquals('Kunjungan dine-in berhasil dicatat.', session('status'));
    }

    public function test_duplicate_visit_same_day_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin, 'admin');
        $customer = Customer::create(['nama' => 'Budi', 'no_wa' => '6281234567890']);
        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => '2026-01-05',
            'id_admin' => $admin->id_admin,
        ]);

        $this->postJson('/admin/visits', [
            'customer_id' => $customer->id_customer,
            'visited_at' => '2026-01-05',
            'visit_type' => 'dine_in',
        ])->assertStatus(422)->assertJsonValidationErrors('customer_id');
    }

    public function test_future_visit_date_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');
        $customer = Customer::create(['nama' => 'C', 'no_wa' => '628222222222']);

        $this->postJson('/admin/visits', [
            'customer_id' => $customer->id_customer,
            'visited_at' => now()->addDay()->toDateString(),
            'visit_type' => 'dine_in',
        ])->assertStatus(422)->assertJsonValidationErrors('visited_at');
    }

    public function test_non_dine_in_visit_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');
        $customer = Customer::create(['nama' => 'D', 'no_wa' => '628333333333']);

        $this->postJson('/admin/visits', [
            'customer_id' => $customer->id_customer,
            'visited_at' => now()->toDateString(),
            'visit_type' => 'take_away',
        ])->assertStatus(422)->assertJsonValidationErrors('visit_type');
    }

    public function test_customers_page_renders_visit_modal_and_button(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');
        $customer = Customer::create(['nama' => 'Budi', 'no_wa' => '6281234567890']);

        $this->get('/admin/customers')
            ->assertOk()
            ->assertSee('id="ang-visit-modal"', false)
            ->assertSee('data-ang-modal="ang-visit-modal"', false)
            ->assertSee('data-customer-id="'.$customer->id_customer.'"', false)
            ->assertSee('data-id="'.$customer->id_customer.'"', false)
            ->assertSee('/admin/visits', false);
    }

    public function test_visit_requires_login(): void
    {
        $this->post('/admin/visits', [
            'customer_id' => 1,
            'visited_at' => now()->toDateString(),
            'visit_type' => 'dine_in',
        ])->assertRedirect(route('login'));
    }
}
