<?php

namespace Tests\Feature;

use App\Console\Commands\SendBirthdayMessages;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Hampers;
use App\Models\Kunjungan;
use App\Models\KuotaHampers;
use App\Models\PesanUlangTahun;
use App\Support\Pengaturan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BirthdayTest extends TestCase
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

    private function claimHampers(Customer $customer): void
    {
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
            'status' => 'DIAMBIL',
            'diambil_at' => now(),
        ]);
    }

    /**
     * Customer yang berulang tahun, lengkap dengan kunjungan
     * pada hari-hari sebelumnya (masuk jendela evaluasi).
     *
     * @param  int  $ulangTahunLalu  berapa hari yang lalu ulang tahunnya (0 = hari ini)
     */
    private function birthdayCustomer(string $nama, int $visits, Admin $admin, string $noWa, int $ulangTahunLalu = 0): Customer
    {
        $ulangTahun = today()->subDays($ulangTahunLalu);

        $customer = Customer::create([
            'nama' => $nama,
            'no_wa' => $noWa,
            'lahir_tgl' => $ulangTahun->day,
            'lahir_bln' => $ulangTahun->month,
            'setuju_pesan' => true,
        ]);

        for ($i = 1; $i <= $visits; $i++) {
            Kunjungan::create([
                'id_customer' => $customer->id_customer,
                'tgl_kunjungan' => $ulangTahun->copy()->subDays($i)->toDateString(),
                'id_admin' => $admin->id_admin,
            ]);
        }

        return $customer;
    }

    public function test_guest_is_redirected_from_birthday_page(): void
    {
        $this->get('/admin/birthday')->assertRedirect(route('login'));
    }

    public function test_birthday_page_is_rendered_with_summary_and_members(): void
    {
        $admin = $this->makeAdmin();

        $this->birthdayCustomer('Andi', 5, $admin, '628511111111');
        Customer::create(['nama' => 'Tanpa Lahir', 'no_wa' => '628522222222']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertSee('Birthday &amp; Hampers', false)
            ->assertSee('Daftar evaluasi member')
            ->assertSee('Andi', false)
            ->assertSee('Pratinjau pesan', false)
            ->assertSee('data-ang-modal="ang-message-modal"', false)
            ->assertViewHas('quotaYear', 100)
            ->assertViewHas('withBirthDate', 1)
            ->assertViewHas('eligibleCount', 1)
            ->assertViewHas('minVisits', 5)
            ->assertViewHas('windowDays', 90)
            ->assertViewHas('members', fn ($members) => $members->count() === 1 && $members->first()->nama === 'Andi');
    }

    public function test_message_modal_is_included_exactly_once(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $html = $this->get(route('admin.birthday.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="ang-message-modal"'));
    }

    public function test_visits_column_shows_fraction_or_qualifies_text(): void
    {
        $admin = $this->makeAdmin();

        // 5 dari 5 (default min_visits) -> teks "Memenuhi syarat".
        $this->birthdayCustomer('Andi', 5, $admin, '628544444444');
        // Baru 3 -> tampil pecahan "3/5".
        $this->birthdayCustomer('Budi', 3, $admin, '628555555555');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertSee('<span class="ang-badge ang-badge-ok">Memenuhi syarat</span>', false)
            ->assertDontSee('<span class="ang-visit">5/5</span>', false)
            ->assertSee('<span class="ang-visit">3/5</span>', false);
    }

    public function test_status_pesan_column_shows_send_status_without_consent_column(): void
    {
        $admin = $this->makeAdmin();
        $terkirim = $this->birthdayCustomer('Tari', 5, $admin, '628566666666');
        $gagal = $this->birthdayCustomer('Galih', 5, $admin, '628577777777');
        $this->birthdayCustomer('Rina', 5, $admin, '628588888888');

        PesanUlangTahun::create([
            'id_customer' => $terkirim->id_customer,
            'tahun' => now()->year,
            'jenis' => PesanUlangTahun::JENIS_HAMPERS,
            'status' => PesanUlangTahun::STATUS_TERKIRIM,
        ]);
        PesanUlangTahun::create([
            'id_customer' => $gagal->id_customer,
            'tahun' => now()->year,
            'jenis' => PesanUlangTahun::JENIS_HAMPERS,
            'status' => PesanUlangTahun::STATUS_GAGAL,
            'error' => 'Gateway down',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertDontSee('<th>Consent WA</th>', false)
            ->assertSee('<th>Status pesan</th>', false)
            ->assertSee('<span class="ang-badge ang-badge-ok">Pesan Terkirim</span>', false)
            ->assertSee('<span class="ang-badge ang-badge-no">Pesan Gagal Dikirim</span>', false)
            // Tiga member tanpa record pesan tahun ini -> "Belum Layak".
            ->assertSee('<span class="ang-badge ang-badge-off">Belum Layak</span>', false)
            ->assertViewHas('members', function ($members) use ($terkirim, $gagal) {
                $byId = $members->keyBy('id_customer');

                return $byId[$terkirim->id_customer]->status_pesan === PesanUlangTahun::STATUS_TERKIRIM
                    && $byId[$gagal->id_customer]->status_pesan === PesanUlangTahun::STATUS_GAGAL
                    && $byId[$gagal->id_customer]->getAttribute('status_pesan') !== null;
            });
    }

    public function test_quota_year_is_read_from_kuota_hampers_table(): void
    {
        $admin = $this->makeAdmin();
        KuotaHampers::create(['tahun' => now()->year, 'kuota_maks' => 40]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertViewHas('quotaYear', 40);
    }

    public function test_visits_window_count_only_counts_visits_before_birthday(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->birthdayCustomer('Budi', 0, $admin, '628533333333');

        // Di dalam jendela: 1 hari sebelum ulang tahun.
        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => today()->subDays(1)->toDateString(),
            'id_admin' => $admin->id_admin,
        ]);
        // Terlalu lama, di luar jendela window_days hari.
        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => today()->subDays(100)->toDateString(),
            'id_admin' => $admin->id_admin,
        ]);
        // Setelah hari ulang tahun, tidak ikut dihitung.
        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => today()->addDays(3)->toDateString(),
            'id_admin' => $admin->id_admin,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertViewHas('members', function ($members) {
                $member = $members->firstWhere('nama', 'Budi');

                return $member !== null
                    && (int) $member->visits_window_count === 1
                    && (int) $member->visits_count === 3;
            });
    }

    public function test_member_without_birth_date_is_not_listed(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        Customer::create(['nama' => 'Tanpa Lahir', 'no_wa' => '628544444444']);
        Customer::create(['nama' => 'Ada Lahir', 'no_wa' => '628555555555', 'lahir_tgl' => 9, 'lahir_bln' => 9]);

        $this->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertDontSee('Tanpa Lahir', false)
            ->assertSee('Ada Lahir', false)
            ->assertViewHas('members', fn ($members) => $members->count() === 1);
    }

    public function test_member_who_already_claimed_hampers_is_not_eligible(): void
    {
        $admin = $this->makeAdmin();
        $claimed = $this->birthdayCustomer('Citra', 5, $admin, '628566666666');
        $this->claimHampers($claimed);

        $this->birthdayCustomer('Dewi', 5, $admin, '628577777777');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertSee('<span class="ang-badge ang-badge-ok">Sudah KLAIM</span>', false)
            // Dewi layak tapi belum ada record -> tombol KLAIM.
            ->assertSee('>KLAIM</button>', false)
            ->assertViewHas('eligibleCount', 1)
            ->assertViewHas('members', fn ($members) => $members->firstWhere('nama', 'Citra')->hampers_eligible === false);
    }

    public function test_member_below_min_visits_is_not_eligible(): void
    {
        $admin = $this->makeAdmin();
        $this->birthdayCustomer('Endah', 4, $admin, '628588888888');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertViewHas('eligibleCount', 0)
            ->assertViewHas('members', fn ($members) => $members->firstWhere('nama', 'Endah')->hampers_eligible === false);
    }

    public function test_min_visits_setting_gates_eligibility(): void
    {
        Pengaturan::simpan('min_visits', '3');
        $admin = $this->makeAdmin();
        $this->birthdayCustomer('Fajar', 3, $admin, '628599999999');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertViewHas('eligibleCount', 1);
    }

    public function test_birthday_page_has_the_stat_styles_it_uses(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $html = $this->get(route('admin.birthday.index'))->assertOk()->getContent();

        // ang-birthday.css menyediakan .ang-stats.is-3 / .ang-fineprint / badge "no".
        $this->assertStringContainsString('assets/css/ang-birthday.css', $html);
        // .ang-panel-title & .ang-panel-sub dipindahkan ke ang-customer.css.
        $this->assertStringContainsString('assets/css/ang-customer.css', $html);
        $this->assertStringContainsString('class="ang-panel-title"', $html);
    }

    private function makePesan(Customer $customer, string $jenis = 'HAMPERS'): int
    {
        return DB::table('pesan_ulang_tahun')->insertGetId([
            'id_customer' => $customer->id_customer,
            'tahun' => now()->year,
            'jenis' => $jenis,
            'status' => 'TERKIRIM',
        ]);
    }

    public function test_status_hampers_shows_claim_button_and_expires_after_deadline(): void
    {
        $admin = $this->makeAdmin();
        $tahun = now()->year;

        // Layak, belum ada record hampers -> tombol KLAIM (route ke customer).
        $siap = $this->birthdayCustomer('Tari', 5, $admin, '628566666666');

        // Lewat batas ambil, belum diklaim -> otomatis KEDALUWARSA + badge.
        $lewat = $this->birthdayCustomer('Galih', 5, $admin, '628577777777');
        Hampers::create([
            'id_customer' => $lewat->id_customer,
            'id_pesan' => $this->makePesan($lewat),
            'tahun_kuota' => $tahun,
            'kode_klaim' => 'HAM-LEWAT1',
            'batas_ambil' => now()->subDay()->toDateString(),
            'status' => 'BELUM_DIAMBIL',
        ]);

        // Sudah pernah diambil -> badge Sudah KLAIM.
        $ambil = $this->birthdayCustomer('Rina', 5, $admin, '628588888888');
        Hampers::create([
            'id_customer' => $ambil->id_customer,
            'id_pesan' => $this->makePesan($ambil),
            'tahun_kuota' => $tahun,
            'kode_klaim' => 'HAM-AMBIL1',
            'batas_ambil' => now()->addDays(3)->toDateString(),
            'status' => 'DIAMBIL',
            'diambil_at' => now(),
        ]);

        // Layak tanpa record, ulang tahun sudah lewat > 7 hari -> Kadaluwarsa.
        $this->birthdayCustomer('Eka', 5, $admin, '628599999999', 10);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertSee('data-claim-action="'.route('admin.hampers.claim', $siap).'"', false)
            ->assertSee('>KLAIM</button>', false)
            ->assertSee('<span class="ang-badge ang-badge-no">Kadaluwarsa</span>', false)
            ->assertSee('<span class="ang-badge ang-badge-ok">Sudah KLAIM</span>', false);

        $this->assertDatabaseHas('hampers', ['kode_klaim' => 'HAM-LEWAT1', 'status' => 'KEDALUWARSA']);
    }

    public function test_claim_route_creates_hampers_and_marks_as_taken(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->birthdayCustomer('Andi', 5, $admin, '628511111111');

        $this->actingAs($admin, 'admin')
            ->from(route('admin.birthday.index'))
            ->patch(route('admin.hampers.claim', $customer))
            ->assertRedirect(route('admin.birthday.index'));

        $hampers = Hampers::query()->where('id_customer', $customer->id_customer)->sole();

        $this->assertSame('DIAMBIL', $hampers->status);
        $this->assertNotNull($hampers->diambil_at);
        $this->assertSame($admin->id_admin, $hampers->id_admin);
        $this->assertNull($hampers->id_pesan);
        $this->assertSame(
            today()->addDays(SendBirthdayMessages::BATAS_HARI)->toDateString(),
            $hampers->batas_ambil->toDateString(),
        );
        $this->assertStringStartsWith('HAM-', $hampers->kode_klaim);
        $this->assertEquals('Hampers '.$hampers->kode_klaim.' ditandai KLAIM.', session('status'));

        // Halaman menampilkan Sudah KLAIM.
        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertSee('<span class="ang-badge ang-badge-ok">Sudah KLAIM</span>', false)
            ->assertDontSee('>KLAIM</button>', false);

        // Klaim kedua kali ditolak dengan pesan.
        $this->actingAs($admin, 'admin')
            ->patch(route('admin.hampers.claim', $customer))
            ->assertRedirect();
        $this->assertEquals('Hampers untuk Andi sudah pernah diklaim.', session('status'));
    }

    public function test_claim_route_rejects_member_past_deadline(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->birthdayCustomer('Eka', 5, $admin, '628599999999', 10);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.hampers.claim', $customer))
            ->assertRedirect();

        $this->assertEquals(
            'Hampers untuk Eka sudah kadaluwarsa dan tidak dapat diklaim.',
            session('status'),
        );
        $this->assertDatabaseCount('hampers', 0);
    }
}
