<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherValidateTest extends TestCase
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

    private function makeVoucher(array $attributes = []): Voucher
    {
        return Voucher::create(array_merge([
            'kode_voucher' => 'BRO-7KQ2M9',
            'kategori' => 'BRO',
            'status' => 'AVAILABLE',
        ], $attributes));
    }

    public function test_guest_is_redirected_from_validation_page(): void
    {
        $this->get('/admin/vouchers/validate')->assertRedirect(route('login'));
    }

    public function test_voucher_exposes_the_fields_the_view_uses(): void
    {
        $voucher = $this->makeVoucher();
        $used = $this->makeVoucher([
            'kode_voucher' => 'IG-AAA111',
            'kategori' => 'IG',
            'status' => 'USED',
        ]);

        $this->assertSame($voucher->id_voucher, $voucher->id);
        $this->assertSame('BRO-7KQ2M9', $voucher->code);
        $this->assertSame('Brosur', $voucher->source_label);
        $this->assertFalse($voucher->is_used);

        $this->assertSame('IG-AAA111', $used->code);
        $this->assertSame('Instagram', $used->source_label);
        $this->assertTrue($used->is_used);
    }

    public function test_validation_page_is_rendered(): void
    {
        $admin = $this->makeAdmin();
        $voucher = $this->makeVoucher();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.vouchers.validate'))
            ->assertOk()
            ->assertSee('Validasi Voucher')
            ->assertSee('Cek kode voucher')
            ->assertSee('name="code"', false)
            ->assertSee(route('admin.vouchers.check'), false)
            ->assertSee('BRO-7KQ2M9', false)
            ->assertViewHas('result', null)
            ->assertViewHas('vouchers', fn ($vouchers) => $vouchers->count() === 1);

        $this->assertSame('BRO-7KQ2M9', $voucher->code);
    }

    public function test_validation_page_loads_shared_assets_exactly_once(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $html = $this->get(route('admin.vouchers.validate'))->assertOk()->getContent();

        $this->assertStringContainsString('assets/css/ang-validate.css', $html);
        $this->assertStringContainsString('assets/js/ang-validate.js', $html);
        $this->assertSame(1, substr_count($html, 'assets/css/ang-modal.css'));
        $this->assertSame(1, substr_count($html, 'assets/js/ang-modal.js'));
        $this->assertSame(1, substr_count($html, 'id="ang-redeem-modal"'));
    }

    public function test_available_voucher_is_reported_as_valid(): void
    {
        $admin = $this->makeAdmin();
        $voucher = $this->makeVoucher();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.vouchers.validate'))
            ->post(route('admin.vouchers.check'), ['code' => 'BRO-7KQ2M9'])
            ->assertRedirect(route('admin.vouchers.validate'));

        $result = session('result');
        $this->assertSame('valid', $result['state']);
        $this->assertSame('BRO-7KQ2M9', $result['code']);
        $this->assertSame($voucher->id_voucher, $result['voucher']->id_voucher);

        $this->get(route('admin.vouchers.validate'))
            ->assertOk()
            ->assertSee('Voucher valid', false)
            ->assertSee('data-redeem-action="'.route('admin.vouchers.redeem', $voucher->id).'"', false);
    }

    public function test_code_is_normalized_before_lookup(): void
    {
        $this->makeVoucher();

        $this->actingAs($this->makeAdmin(), 'admin')
            ->from(route('admin.vouchers.validate'))
            ->post(route('admin.vouchers.check'), ['code' => '  bro-7kq2m9  '])
            ->assertRedirect(route('admin.vouchers.validate'));

        $this->assertSame('valid', session('result')['state']);
        $this->assertSame('BRO-7KQ2M9', session('result')['code']);
    }

    public function test_unknown_code_is_reported_as_invalid(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin')
            ->from(route('admin.vouchers.validate'))
            ->post(route('admin.vouchers.check'), ['code' => 'BRO-XXXXXX'])
            ->assertRedirect(route('admin.vouchers.validate'));

        $result = session('result');
        $this->assertSame('invalid', $result['state']);
        $this->assertNull($result['voucher']);

        $this->get(route('admin.vouchers.validate'))
            ->assertOk()
            ->assertSee('Kode tidak ditemukan', false);
    }

    public function test_used_voucher_is_reported_as_used(): void
    {
        $this->makeVoucher([
            'status' => 'USED',
            'used_at' => '2026-01-05 14:30:00',
        ]);

        $this->actingAs($this->makeAdmin(), 'admin')
            ->from(route('admin.vouchers.validate'))
            ->post(route('admin.vouchers.check'), ['code' => 'BRO-7KQ2M9'])
            ->assertRedirect(route('admin.vouchers.validate'));

        $this->assertSame('used', session('result')['state']);

        $this->get(route('admin.vouchers.validate'))
            ->assertOk()
            ->assertSee('Voucher sudah digunakan', false)
            ->assertSee('05 Jan 2026, 14:30', false);
    }

    public function test_empty_or_too_long_code_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->from(route('admin.vouchers.validate'))
            ->post(route('admin.vouchers.check'), [])
            ->assertRedirect(route('admin.vouchers.validate'))
            ->assertSessionHasErrors('code');

        $this->postJson(route('admin.vouchers.check'), ['code' => 'KODE-TERLALU-PANJANG'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_available_voucher_can_be_marked_used(): void
    {
        $admin = $this->makeAdmin();
        $voucher = $this->makeVoucher();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.vouchers.validate'))
            ->patch(route('admin.vouchers.redeem', $voucher->id))
            ->assertRedirect(route('admin.vouchers.validate'));

        $this->assertDatabaseHas('voucher', [
            'id_voucher' => $voucher->id_voucher,
            'status' => 'USED',
            'id_admin' => $admin->id_admin,
        ]);
        $this->assertNotNull($voucher->fresh()->used_at);
        $this->assertEquals('Voucher BRO-7KQ2M9 ditandai USED.', session('status'));
    }

    public function test_used_voucher_cannot_be_redeemed_twice(): void
    {
        $voucher = $this->makeVoucher([
            'status' => 'USED',
            'used_at' => '2026-01-05 14:30:00',
        ]);

        $this->actingAs($this->makeAdmin(), 'admin')
            ->from(route('admin.vouchers.validate'))
            ->patch(route('admin.vouchers.redeem', $voucher->id))
            ->assertRedirect(route('admin.vouchers.validate'));

        $this->assertEquals('Voucher BRO-7KQ2M9 sudah ditandai USED.', session('status'));
        $this->assertEquals('USED', $voucher->fresh()->status);
    }

    public function test_table_shows_available_voucher_state(): void
    {
        $this->makeVoucher();

        $this->actingAs($this->makeAdmin(), 'admin')
            ->get(route('admin.vouchers.validate'))
            ->assertOk()
            ->assertSee('Belum digunakan', false)
            ->assertSee('Tandai USED', false);
    }
}
