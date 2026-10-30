<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Support\Pengaturan;
use App\Support\VoucherKuota;
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

    private function insertVoucher(string $code, string $kategori, string $createdAt = '2026-01-05 10:00:00', string $status = 'AVAILABLE', ?string $downloadedAt = null): void
    {
        DB::table('voucher')->insert([
            'kode_voucher' => $code,
            'kategori' => $kategori,
            'status' => $status,
            'created_at' => $createdAt,
            'used_at' => $status === 'USED' ? $createdAt : null,
            'downloaded_at' => $downloadedAt,
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
            ->assertViewHas('limits', fn ($limits) => $limits['brochure'] === VoucherKuota::DEFAULT_BROCHURE && $limits['digital'] === VoucherKuota::DEFAULT_DIGITAL)
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

    public function test_print_modal_always_uses_one_code_per_voucher(): void
    {
        $html = $this->actingAs($this->makeAdmin(), 'admin')
            ->get(route('admin.vouchers.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-print-scope', $html);
        // Pilihan "kode per lembar" dihapus: defaultnya satu kode per voucher.
        $this->assertStringNotContainsString('data-print-mode', $html);
        $this->assertStringNotContainsString('Satu kode di semua voucher', $html);
        $this->assertStringContainsString('kode berbeda', $html);
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

    public function test_print_modal_offers_custom_range_option(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin')
            ->get(route('admin.vouchers.index'))
            ->assertOk()
            ->assertSee('Rentang custom', false)
            ->assertSee('data-mark-url', false);
    }

    public function test_codes_endpoint_excludes_already_downloaded_codes(): void
    {
        $this->insertVoucher('BRO-OLD001', 'BRO', '2026-01-04 09:00:00', 'AVAILABLE', '2026-01-06 08:00:00');
        $this->insertVoucher('BRO-OLD002', 'BRO', '2026-01-04 09:00:00');
        $this->insertVoucher('BRO-NEW001', 'BRO', '2026-01-05 10:00:00', 'AVAILABLE', '2026-01-06 08:00:00');
        $this->insertVoucher('BRO-NEW002', 'BRO', '2026-01-05 10:00:00');

        $this->actingAs($this->makeAdmin(), 'admin')
            ->getJson(route('admin.vouchers.codes'))
            ->assertOk()
            ->assertJson(['source' => 'BRO', 'total' => 2, 'downloaded' => 2])
            ->assertJsonCount(2, 'codes')
            ->assertJsonPath('codes.0', 'BRO-NEW002')
            ->assertJsonPath('codes.1', 'BRO-OLD002')
            ->assertJsonCount(1, 'latest')
            ->assertJsonPath('latest.0', 'BRO-NEW002');
    }

    public function test_codes_endpoint_returns_all_codes_with_stable_oldest_first_positions(): void
    {
        $this->insertVoucher('BRO-OLD001', 'BRO', '2026-01-04 09:00:00', 'AVAILABLE', '2026-01-06 08:00:00');
        $this->insertVoucher('BRO-OLD002', 'BRO', '2026-01-04 09:00:00');
        $this->insertVoucher('BRO-NEW001', 'BRO', '2026-01-05 10:00:00', 'AVAILABLE', '2026-01-06 08:00:00');
        $this->insertVoucher('BRO-NEW002', 'BRO', '2026-01-05 10:00:00');

        $this->actingAs($this->makeAdmin(), 'admin')
            ->getJson(route('admin.vouchers.codes'))
            ->assertOk()
            ->assertJson(['total' => 2, 'total_all' => 4, 'downloaded' => 2])
            ->assertJsonCount(4, 'all')
            ->assertJsonPath('all.0.code', 'BRO-OLD001')
            ->assertJsonPath('all.0.downloaded', true)
            ->assertJsonPath('all.1.code', 'BRO-OLD002')
            ->assertJsonPath('all.1.downloaded', false)
            ->assertJsonPath('all.2.code', 'BRO-NEW001')
            ->assertJsonPath('all.2.downloaded', true)
            ->assertJsonPath('all.3.code', 'BRO-NEW002')
            ->assertJsonPath('all.3.downloaded', false);
    }

    public function test_marking_download_keeps_range_positions_stable(): void
    {
        $this->insertVoucher('BRO-AAA111', 'BRO');
        $this->insertVoucher('BRO-AAA222', 'BRO');
        $this->insertVoucher('BRO-AAA333', 'BRO');
        $admin = $this->makeAdmin();

        // Unduh dua kode terdepan (nomor 1 dan 2 pada urutan paling lama dulu).
        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.vouchers.downloaded'), [
                'source' => 'BRO',
                'codes' => ['BRO-AAA111', 'BRO-AAA222'],
            ])
            ->assertOk()
            ->assertJson(['marked' => 2]);

        // Nomor tidak bergeser: kode tetap di posisi semula, hanya bertanda terunduh.
        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.vouchers.codes'))
            ->assertOk()
            ->assertJson(['total' => 1, 'total_all' => 3, 'downloaded' => 2])
            ->assertJsonCount(3, 'all')
            ->assertJsonPath('all.0.code', 'BRO-AAA111')
            ->assertJsonPath('all.0.downloaded', true)
            ->assertJsonPath('all.1.code', 'BRO-AAA222')
            ->assertJsonPath('all.1.downloaded', true)
            ->assertJsonPath('all.2.code', 'BRO-AAA333')
            ->assertJsonPath('all.2.downloaded', false)
            ->assertJsonCount(1, 'codes')
            ->assertJsonPath('codes.0', 'BRO-AAA333');
    }

    public function test_codes_endpoint_reports_empty_when_entire_latest_batch_downloaded(): void
    {
        $this->insertVoucher('BRO-NEW001', 'BRO', '2026-01-05 10:00:00', 'AVAILABLE', '2026-01-06 08:00:00');
        $this->insertVoucher('BRO-NEW002', 'BRO', '2026-01-05 10:00:00', 'AVAILABLE', '2026-01-06 08:00:00');

        $this->actingAs($this->makeAdmin(), 'admin')
            ->getJson(route('admin.vouchers.codes'))
            ->assertOk()
            ->assertJson(['source' => 'BRO', 'total' => 0, 'downloaded' => 2])
            ->assertJsonCount(0, 'codes')
            ->assertJsonCount(0, 'latest');
    }

    public function test_mark_downloaded_marks_available_codes_of_that_channel(): void
    {
        $this->insertVoucher('BRO-AAA111', 'BRO');
        $this->insertVoucher('BRO-AAA222', 'BRO');
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.vouchers.downloaded'), [
                'source' => 'BRO',
                'codes' => ['BRO-AAA111', 'BRO-AAA222'],
            ])
            ->assertOk()
            ->assertJson(['source' => 'BRO', 'requested' => 2, 'marked' => 2]);

        $this->assertNotNull(DB::table('voucher')->where('kode_voucher', 'BRO-AAA111')->value('downloaded_at'));
        $this->assertNotNull(DB::table('voucher')->where('kode_voucher', 'BRO-AAA222')->value('downloaded_at'));

        // Panggilan ulang tidak menandai apa pun lagi (idempoten).
        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.vouchers.downloaded'), [
                'source' => 'BRO',
                'codes' => ['BRO-AAA111', 'BRO-AAA222'],
            ])
            ->assertOk()
            ->assertJson(['marked' => 0]);
    }

    public function test_mark_downloaded_ignores_unknown_used_and_other_channel_codes(): void
    {
        $this->insertVoucher('BRO-USED01', 'BRO', '2026-01-05 10:00:00', 'USED');
        $this->insertVoucher('IG-OTHER1', 'IG');

        $this->actingAs($this->makeAdmin(), 'admin')
            ->patchJson(route('admin.vouchers.downloaded'), [
                'source' => 'BRO',
                'codes' => ['BRO-USED01', 'IG-OTHER1', 'BRO-TIDAK9'],
            ])
            ->assertOk()
            ->assertJson(['marked' => 0]);

        $this->assertNull(DB::table('voucher')->where('kode_voucher', 'IG-OTHER1')->value('downloaded_at'));
        $this->assertNull(DB::table('voucher')->where('kode_voucher', 'BRO-USED01')->value('downloaded_at'));
    }

    public function test_mark_downloaded_requires_code_list(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin')
            ->patchJson(route('admin.vouchers.downloaded'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('codes');
    }

    public function test_guest_cannot_mark_codes_as_downloaded(): void
    {
        $this->insertVoucher('BRO-AAA111', 'BRO');

        $this->patchJson(route('admin.vouchers.downloaded'), [
            'source' => 'BRO',
            'codes' => ['BRO-AAA111'],
        ])->assertUnauthorized();

        $this->assertNull(DB::table('voucher')->where('kode_voucher', 'BRO-AAA111')->value('downloaded_at'));
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

    public function test_export_pdf_follows_search_filters(): void
    {
        $this->insertVoucher('IG-AAA111', 'IG', '2026-01-04 09:00:00');
        $this->insertVoucher('BRO-BBB222', 'BRO', '2026-01-05 10:00:00');
        $this->actingAs($this->makeAdmin(), 'admin');

        $pdf = $this->get(route('admin.vouchers.export.pdf'))
            ->assertOk()
            ->assertDownload();
        $this->assertStringContainsString('.pdf', (string) $pdf->headers->get('content-disposition'));

        $this->get(route('admin.vouchers.export.pdf', ['source' => 'BRO', 'q' => 'BRO-B']))
            ->assertOk()
            ->assertDownload();
    }
}
