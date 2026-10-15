<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Support\Pengaturan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VoucherManagementTest extends TestCase
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

    private function insertVoucher(string $code, string $kategori, string $createdAt = '2026-01-05 10:00:00', string $status = 'AVAILABLE'): void
    {
        DB::table('voucher')->insert([
            'kode_voucher' => $code,
            'kategori' => $kategori,
            'status' => $status,
            'created_at' => $createdAt,
            'used_at' => $status === 'USED' ? $createdAt : null,
        ]);
    }

    public function test_guest_is_redirected_from_management_page(): void
    {
        $this->get('/admin/vouchers')->assertRedirect(route('login'));
    }

    public function test_management_page_renders_channels_table_and_modals(): void
    {
        $this->insertVoucher('BRO-AAA111', 'BRO');

        $html = $this->actingAs($this->makeAdmin(), 'admin')
            ->get(route('admin.vouchers.index'))
            ->assertOk()
            ->assertSee('Manajemen Voucher')
            ->assertSee('BRO-AAA111', false)
            ->assertViewHas('channels', fn ($channels) => count($channels) === 4
                && $channels[0]['code'] === 'IG'
                && $channels[3]['code'] === 'BRO'
                && $channels[3]['count'] === 1)
            ->assertViewHas('limits', fn ($limits) => $limits['brochure'] === 2000 && $limits['digital'] === 50)
            ->assertViewHas('vouchers', fn ($vouchers) => $vouchers->total() === 1)
            ->assertViewHas('voucherTemplate', fn ($url) => str_ends_with($url, 'images/brosur-voucher.jpeg'))
            ->getContent();

        $this->assertSame(1, substr_count($html, 'id="ang-gen-modal"'));
        $this->assertSame(1, substr_count($html, 'id="ang-print-modal"'));
        $this->assertSame(1, substr_count($html, 'id="ang-redeem-modal"'));
        // ang-modal.css & ang-modal.js sudah dimuat layout.
        $this->assertSame(1, substr_count($html, 'assets/css/ang-modal.css'));
        $this->assertSame(1, substr_count($html, 'assets/js/ang-modal.js'));
        $this->assertStringContainsString('assets/css/ang-voucher.css', $html);
        $this->assertStringContainsString('assets/js/ang-vouchers.js', $html);
        $this->assertStringContainsString('assets/js/vendor/fflate.min.js', $html);
        $this->assertStringContainsString(route('admin.vouchers.export'), $html);
        $this->assertFileExists(public_path('images/brosur-voucher.jpeg'));
    }

    public function test_dashboard_manage_voucher_button_points_to_management_page(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.vouchers.index'), false);
    }

    public function test_vouchers_can_be_filtered_by_keyword_and_channel(): void
    {
        $this->insertVoucher('IG-AAA111', 'IG', '2026-01-04 09:00:00');
        $this->insertVoucher('BRO-BBB222', 'BRO');
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->get(route('admin.vouchers.index'))
            ->assertOk()
            ->assertSee('IG-AAA111', false)
            ->assertSee('BRO-BBB222', false);

        $this->get(route('admin.vouchers.index', ['q' => 'BRO-']))
            ->assertOk()
            ->assertSee('BRO-BBB222', false)
            ->assertDontSee('IG-AAA111', false);

        $this->get(route('admin.vouchers.index', ['source' => 'IG']))
            ->assertOk()
            ->assertSee('IG-AAA111', false)
            ->assertDontSee('BRO-BBB222', false);
    }

    public function test_generate_creates_unique_prefixed_codes(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.vouchers.generate'), ['source' => 'IG', 'quantity' => 3])
            ->assertRedirect(route('admin.vouchers.index'));

        $this->assertEquals('3 kode voucher Instagram berhasil dibuat.', session('status'));

        $rows = DB::table('voucher')->get();
        $this->assertCount(3, $rows);
        $this->assertSame(
            3,
            $rows->filter(fn ($row) => str_starts_with($row->kode_voucher, 'IG-')
                && $row->status === 'AVAILABLE'
                && $row->kategori === 'IG')->count()
        );
        $this->assertSame(3, $rows->pluck('kode_voucher')->unique()->count());
        $this->assertLessThanOrEqual(10, strlen($rows->first()->kode_voucher));
    }

    public function test_generate_rejects_unknown_source_and_invalid_quantity(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->post(route('admin.vouchers.generate'), ['source' => 'WA', 'quantity' => 3])
            ->assertSessionHasErrors('source');

        $this->post(route('admin.vouchers.generate'), ['source' => 'IG', 'quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->post(route('admin.vouchers.generate'), ['source' => 'IG', 'quantity' => 'banyak'])
            ->assertSessionHasErrors('quantity');

        $this->post(route('admin.vouchers.generate'), [])
            ->assertSessionHasErrors(['source', 'quantity']);

        $this->assertDatabaseCount('voucher', 0);
    }

    public function test_generate_shares_digital_quota_across_ig_fb_and_tt(): void
    {
        Pengaturan::simpan('limit_voucher_digital', '3');
        $this->insertVoucher('IG-AAA111', 'IG');
        $this->insertVoucher('IG-BBB222', 'IG');
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->post(route('admin.vouchers.generate'), ['source' => 'FB', 'quantity' => 2])
            ->assertSessionHasErrors('quantity');

        $errors = session('errors')->first('quantity');
        $this->assertEquals('Jumlah melebihi sisa kuota 1 kode.', $errors);

        $this->post(route('admin.vouchers.generate'), ['source' => 'FB', 'quantity' => 1])
            ->assertRedirect(route('admin.vouchers.index'));

        $this->assertSame(3, DB::table('voucher')->count());
        $this->assertSame(1, DB::table('voucher')->where('kategori', 'FB')->count());

        $this->post(route('admin.vouchers.generate'), ['source' => 'IG', 'quantity' => 1])
            ->assertSessionHasErrors('quantity');

        $this->assertEquals('Kuota voucher IG sudah habis.', session('errors')->first('quantity'));
    }

    public function test_generate_is_limited_by_brochure_quota(): void
    {
        Pengaturan::simpan('limit_voucher_brochure', '1');
        $this->insertVoucher('BRO-AAA111', 'BRO');
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->post(route('admin.vouchers.generate'), ['source' => 'BRO', 'quantity' => 1])
            ->assertSessionHasErrors('quantity');

        $this->assertEquals('Kuota voucher BRO sudah habis.', session('errors')->first('quantity'));
        $this->assertDatabaseCount('voucher', 1);
    }

    public function test_codes_endpoint_returns_available_codes_and_latest_batch(): void
    {
        // Batch lama.
        $this->insertVoucher('BRO-OLD001', 'BRO', '2026-01-04 09:00:00');
        $this->insertVoucher('BRO-OLD002', 'BRO', '2026-01-04 09:00:00');
        // Batch terakhir (satu detik sama).
        $this->insertVoucher('BRO-NEW001', 'BRO', '2026-01-05 10:00:00');
        $this->insertVoucher('BRO-NEW002', 'BRO', '2026-01-05 10:00:00');
        $this->insertVoucher('BRO-NEW003', 'BRO', '2026-01-05 10:00:00', 'USED');
        // Kanal lain tidak ikut.
        $this->insertVoucher('IG-OTHER1', 'IG', '2026-01-05 10:00:00');

        $response = $this->actingAs($this->makeAdmin(), 'admin')
            ->getJson(route('admin.vouchers.codes'))
            ->assertOk()
            ->assertJson(['source' => 'BRO', 'total' => 4]);

        $response->assertJsonCount(4, 'codes');
        $response->assertJsonCount(2, 'latest');
        $response->assertJsonPath('codes.0', 'BRO-NEW002');
        $response->assertJsonPath('latest.0', 'BRO-NEW002');
        $response->assertJsonPath('latest.1', 'BRO-NEW001');
        $response->assertJsonMissingPath('codes.5');
    }

    public function test_codes_endpoint_defaults_to_brochure_and_ignores_unknown_source(): void
    {
        $this->insertVoucher('BRO-AAA111', 'BRO');
        $this->insertVoucher('IG-BBB222', 'IG');

        $this->actingAs($this->makeAdmin(), 'admin')
            ->getJson(route('admin.vouchers.codes', ['source' => 'WA']))
            ->assertOk()
            ->assertJson(['source' => 'BRO', 'total' => 1])
            ->assertJsonPath('codes.0', 'BRO-AAA111');
    }

    public function test_codes_endpoint_can_return_another_channel(): void
    {
        $this->insertVoucher('BRO-AAA111', 'BRO');
        $this->insertVoucher('IG-BBB222', 'IG');

        $this->actingAs($this->makeAdmin(), 'admin')
            ->getJson(route('admin.vouchers.codes', ['source' => 'IG']))
            ->assertOk()
            ->assertJson(['source' => 'IG', 'total' => 1])
            ->assertJsonPath('codes.0', 'IG-BBB222');
    }

    public function test_generate_batch_becomes_the_latest_batch_for_printing(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin')
            ->post(route('admin.vouchers.generate'), ['source' => 'BRO', 'quantity' => 3])
            ->assertRedirect(route('admin.vouchers.index'));

        $this->getJson(route('admin.vouchers.codes'))
            ->assertOk()
            ->assertJson(['source' => 'BRO', 'total' => 3])
            ->assertJsonCount(3, 'codes')
            ->assertJsonCount(3, 'latest');
    }

    public function test_export_csv_follows_search_filters(): void
    {
        $this->insertVoucher('IG-AAA111', 'IG', '2026-01-04 09:00:00');
        $this->insertVoucher('BRO-BBB222', 'BRO', '2026-01-05 10:00:00');
        $this->actingAs($this->makeAdmin(), 'admin');

        $semua = $this->get(route('admin.vouchers.export'))
            ->assertOk()
            ->assertDownload()
            ->streamedContent();

        $this->assertStringContainsString('Kode,Sumber,Status,Dibuat,Digunakan', $semua);
        $this->assertStringContainsString('IG-AAA111', $semua);
        $this->assertStringContainsString('BRO-BBB222', $semua);

        $filtered = $this->get(route('admin.vouchers.export', ['source' => 'BRO', 'q' => 'BRO-B']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('BRO-BBB222', $filtered);
        $this->assertStringNotContainsString('IG-AAA111', $filtered);
    }
}
