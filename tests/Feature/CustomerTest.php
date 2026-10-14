<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Kunjungan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
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

    public function test_no_wa_is_normalized_on_create(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->post('/admin/customers', [
            'nama' => 'Budi',
            'no_wa' => '0812-3456-7890',
            'setuju_pesan' => true,
        ])->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('customer', ['no_wa' => '6281234567890']);
        $this->assertEquals('Customer berhasil disimpan.', session('status'));
    }

    public function test_no_wa_must_be_unique(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');
        Customer::create(['nama' => 'A', 'no_wa' => '628111111111']);

        $this->postJson('/admin/customers', [
            'nama' => 'B',
            'no_wa' => '08111111111',
        ])->assertStatus(422)->assertJsonValidationErrors('no_wa');
    }

    public function test_birthdate_pair_must_be_filled_together(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->postJson('/admin/customers', [
            'nama' => 'C',
            'no_wa' => '628222222222',
            'lahir_bln' => 5,
        ])->assertStatus(422)->assertJsonValidationErrors('lahir_tgl');
    }

    public function test_total_kunjungan_is_computed_from_kunjungan(): void
    {
        $admin = $this->makeAdmin();
        $customer = Customer::create(['nama' => 'D', 'no_wa' => '628333333333']);

        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => '2026-01-01',
            'id_admin' => $admin->id_admin,
        ]);
        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => '2026-02-01',
            'id_admin' => $admin->id_admin,
        ]);

        $this->assertSame(2, $customer->fresh()->visits_count);

        $this->actingAs($admin, 'admin');
        $this->get('/admin/customers')
            ->assertOk()
            ->assertViewHas('customers', fn ($customers) => (int) $customers->sum('visits_count') === 2);
    }

    public function test_customer_can_be_searched_by_name_and_birth_month(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');
        Customer::create(['nama' => 'Andi', 'no_wa' => '628555555551', 'lahir_tgl' => 1, 'lahir_bln' => 5]);
        Customer::create(['nama' => 'Budi', 'no_wa' => '628555555552', 'lahir_tgl' => 2, 'lahir_bln' => 6]);

        $this->get('/admin/customers?q=Andi')
            ->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->count() === 1 && $customers->first()->nama === 'Andi');

        $this->get('/admin/customers?bulan=6')
            ->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->count() === 1 && $customers->first()->nama === 'Budi');
    }

    public function test_customers_can_be_exported_to_csv_and_pdf(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');
        Customer::create(['nama' => 'Eko', 'no_wa' => '628444444444']);

        $csv = $this->get(route('admin.customers.export'));
        $csv->assertOk();
        $this->assertStringStartsWith('text/csv', $csv->headers->get('content-type'));

        $this->get(route('admin.customers.export.pdf'))->assertOk();
    }

    public function test_customer_can_be_updated_via_form(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');
        $customer = Customer::create([
            'nama' => 'Budi',
            'no_wa' => '6281234567890',
            'lahir_tgl' => 12,
            'lahir_bln' => 5,
            'setuju_pesan' => true,
        ]);

        $this->put('/admin/customers/'.$customer->id_customer, [
            '_ang_form' => 'customer',
            '_ang_edit' => $customer->id_customer,
            'nama' => 'Budi Santoso',
            'no_wa' => '6281234567890',
            'birth_date' => '1999-03-08',
            'setuju_pesan' => '0',
        ])->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('customer', [
            'id_customer' => $customer->id_customer,
            'nama' => 'Budi Santoso',
            'lahir_tgl' => 8,
            'lahir_bln' => 3,
            'setuju_pesan' => false,
        ]);

        $this->assertEquals('Customer berhasil diperbarui.', session('status'));
    }

    public function test_update_can_clear_birth_date(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');
        $customer = Customer::create([
            'nama' => 'C',
            'no_wa' => '628333333333',
            'lahir_tgl' => 5,
            'lahir_bln' => 4,
        ]);

        $this->put('/admin/customers/'.$customer->id_customer, [
            'nama' => 'C',
            'no_wa' => '628333333333',
            'birth_date' => '',
        ])->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('customer', [
            'id_customer' => $customer->id_customer,
            'lahir_tgl' => null,
            'lahir_bln' => null,
        ]);
    }

    public function test_update_rejects_duplicate_no_wa(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');
        Customer::create(['nama' => 'A', 'no_wa' => '628111111111']);
        $b = Customer::create(['nama' => 'B', 'no_wa' => '628222222222']);

        $this->putJson('/admin/customers/'.$b->id_customer, [
            'nama' => 'B',
            'no_wa' => '628111111111',
        ])->assertStatus(422)->assertJsonValidationErrors('no_wa');
    }

    public function test_customers_page_renders_edit_button(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');
        $customer = Customer::create(['nama' => 'Andi', 'no_wa' => '628555555551', 'lahir_tgl' => 1, 'lahir_bln' => 5]);

        $this->get('/admin/customers')
            ->assertOk()
            ->assertSee('data-edit-id="'.$customer->id_customer.'"', false)
            ->assertSee('data-edit-nama="Andi"', false)
            ->assertSee('data-edit-birth="'.sprintf('%04d-%02d-%02d', 2000, 5, 1).'"', false)
            ->assertSee('id="ang-customer-form"', false)
            ->assertSee('data-store-action="', false);
    }
}
