<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Hampers;
use App\Models\Kunjungan;
use App\Models\KuotaHampers;
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

    public function test_guest_is_redirected_from_birthday_page(): void
    {
        $this->get('/admin/birthday')->assertRedirect(route('login'));
    }

    public function test_birthday_page_is_rendered_with_summary_and_members(): void
    {
        $admin = $this->makeAdmin();

        Customer::create([
            'nama' => 'Andi',
            'no_wa' => '628511111111',
            'lahir_tgl' => 1,
            'lahir_bln' => 5,
            'setuju_pesan' => true,
        ]);
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

    public function test_visits_window_count_only_counts_recent_visits(): void
    {
        $admin = $this->makeAdmin();
        $customer = Customer::create([
            'nama' => 'Budi',
            'no_wa' => '628533333333',
            'lahir_tgl' => 12,
            'lahir_bln' => 3,
        ]);

        // Di dalam jendela 90 hari.
        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => now()->subDays(89)->toDateString(),
            'id_admin' => $admin->id_admin,
        ]);
        // Di luar jendela 90 hari.
        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => now()->subDays(100)->toDateString(),
            'id_admin' => $admin->id_admin,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertViewHas('members', function ($members) {
                $member = $members->firstWhere('nama', 'Budi');

                return $member !== null
                    && (int) $member->visits_window_count === 1
                    && (int) $member->visits_count === 2;
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
        $claimed = Customer::create([
            'nama' => 'Citra',
            'no_wa' => '628566666666',
            'lahir_tgl' => 3,
            'lahir_bln' => 7,
        ]);
        $this->claimHampers($claimed);

        Customer::create([
            'nama' => 'Dewi',
            'no_wa' => '628577777777',
            'lahir_tgl' => 4,
            'lahir_bln' => 8,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertSee('Ucapan saja', false)
            ->assertSee('Layak dievaluasi', false)
            ->assertViewHas('eligibleCount', 1)
            ->assertViewHas('members', fn ($members) => $members->firstWhere('nama', 'Citra')->hampers_eligible === false);
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
