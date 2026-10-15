<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Hampers;
use App\Models\Kunjungan;
use App\Models\KuotaHampers;
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
            'status' => 'BELUM_DIAMBIL',
        ]);
    }

    /**
     * Customer yang berulang tahun hari ini, lengkap dengan kunjungan
     * pada hari-hari sebelumnya (masuk jendela evaluasi).
     */
    private function birthdayCustomer(string $nama, int $visits, Admin $admin, string $noWa): Customer
    {
        $customer = Customer::create([
            'nama' => $nama,
            'no_wa' => $noWa,
            'lahir_tgl' => today()->day,
            'lahir_bln' => today()->month,
            'setuju_pesan' => true,
        ]);

        for ($i = 1; $i <= $visits; $i++) {
            Kunjungan::create([
                'id_customer' => $customer->id_customer,
                'tgl_kunjungan' => today()->subDays($i)->toDateString(),
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
            ->assertSee('Ucapan saja', false)
            ->assertSee('Layak dievaluasi', false)
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
}
