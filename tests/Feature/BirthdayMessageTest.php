<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Hampers;
use App\Models\Kunjungan;
use App\Models\KuotaHampers;
use App\Models\PesanUlangTahun;
use App\Services\WhatsApp\PengirimPesan;
use App\Support\Pengaturan;
use App\Support\TemplatePesanUlangTahun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BirthdayMessageTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'nama' => 'Admin Uji',
            'email' => 'admin@example.test',
            'password' => 'rahasia123',
        ]);
    }

    private function fakePengirim(): object
    {
        $fake = new class implements PengirimPesan
        {
            public array $kirim = [];

            public ?\Throwable $gagal = null;

            public function kirim(string $nomor, string $pesan): string
            {
                if ($this->gagal) {
                    throw $this->gagal;
                }

                $this->kirim[] = ['nomor' => $nomor, 'pesan' => $pesan];

                return 'msg-'.count($this->kirim);
            }
        };

        $this->app->instance(PengirimPesan::class, $fake);

        return $fake;
    }

    private function birthdayCustomer(array $attributes = [], int $visits = 5): Customer
    {
        $customer = Customer::create(array_merge([
            'nama' => 'Andi',
            'no_wa' => '628511111111',
            'lahir_tgl' => today()->day,
            'lahir_bln' => today()->month,
            'setuju_pesan' => true,
        ], $attributes));

        for ($i = 1; $i <= $visits; $i++) {
            Kunjungan::create([
                'id_customer' => $customer->id_customer,
                'tgl_kunjungan' => today()->subDays($i)->toDateString(),
                'id_admin' => $this->admin->id_admin,
            ]);
        }

        return $customer;
    }

    public function test_eligible_member_gets_hampers_message_and_claim_code(): void
    {
        $pengirim = $this->fakePengirim();
        $customer = $this->birthdayCustomer();

        $this->artisan('birthday:send')->assertExitCode(0);

        $this->assertCount(1, $pengirim->kirim);
        $this->assertSame('628511111111', $pengirim->kirim[0]['nomor']);
        $this->assertStringContainsString('klaim hampers ulang tahunmu', $pengirim->kirim[0]['pesan']);
        $this->assertStringContainsString('Andi', $pengirim->kirim[0]['pesan']);

        $pesan = PesanUlangTahun::query()->sole();
        $this->assertSame(PesanUlangTahun::JENIS_HAMPERS, $pesan->jenis);
        $this->assertSame(PesanUlangTahun::STATUS_TERKIRIM, $pesan->status);
        $this->assertSame('msg-1', $pesan->wa_message_id);
        $this->assertNotNull($pesan->dikirim_at);
        $this->assertSame((int) today()->year, (int) $pesan->tahun);

        $hampers = Hampers::query()->sole();
        $this->assertSame($customer->id_customer, $hampers->id_customer);
        $this->assertSame($pesan->id_pesan, $hampers->id_pesan);
        $this->assertSame((int) today()->year, (int) $hampers->tahun_kuota);
        $this->assertSame('BELUM_DIAMBIL', $hampers->status);
        $this->assertSame(today()->addDays(14)->toDateString(), $hampers->batas_ambil->toDateString());
        $this->assertMatchesRegularExpression('/^HAM-[A-Z0-9]{6}$/', $hampers->kode_klaim);
        $this->assertSame(TemplatePesanUlangTahun::layak($customer), $pengirim->kirim[0]['pesan']);
    }

    public function test_member_below_min_visits_gets_greeting_only(): void
    {
        $pengirim = $this->fakePengirim();
        $this->birthdayCustomer(['nama' => 'Budi', 'no_wa' => '628522222222'], 4);

        $this->artisan('birthday:send')->assertExitCode(0);

        $this->assertCount(1, $pengirim->kirim);
        $this->assertStringNotContainsString('hampers ulang tahunmu', $pengirim->kirim[0]['pesan']);

        $pesan = PesanUlangTahun::query()->sole();
        $this->assertSame(PesanUlangTahun::JENIS_BIASA, $pesan->jenis);
        $this->assertSame(PesanUlangTahun::STATUS_TERKIRIM, $pesan->status);
        $this->assertDatabaseCount('hampers', 0);
    }

    public function test_visits_after_birthday_and_outside_window_are_not_counted(): void
    {
        $this->fakePengirim();
        $customer = $this->birthdayCustomer(['nama' => 'Citra', 'no_wa' => '628533333333'], 4);

        // Lima kunjungan total, tapi hanya 4 yang berada sebelum hari ulang tahun.
        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => today()->addDays(3)->toDateString(),
            'id_admin' => $this->admin->id_admin,
        ]);
        Kunjungan::create([
            'id_customer' => $customer->id_customer,
            'tgl_kunjungan' => today()->subDays(100)->toDateString(),
            'id_admin' => $this->admin->id_admin,
        ]);

        $this->artisan('birthday:send')->assertExitCode(0);

        $this->assertSame(PesanUlangTahun::JENIS_BIASA, PesanUlangTahun::query()->sole()->jenis);
        $this->assertDatabaseCount('hampers', 0);
    }

    public function test_member_without_consent_is_skipped(): void
    {
        $pengirim = $this->fakePengirim();
        $this->birthdayCustomer([
            'nama' => 'Dewi',
            'no_wa' => '628544444444',
            'setuju_pesan' => false,
        ]);

        $this->artisan('birthday:send')->assertExitCode(0);

        $this->assertCount(0, $pengirim->kirim);
        $this->assertDatabaseCount('pesan_ulang_tahun', 0);
    }

    public function test_member_with_another_birthday_is_untouched(): void
    {
        $pengirim = $this->fakePengirim();
        Customer::create([
            'nama' => 'Endah',
            'no_wa' => '628555555555',
            'lahir_tgl' => today()->addMonth()->day,
            'lahir_bln' => today()->addMonth()->month,
            'setuju_pesan' => true,
        ]);

        $this->artisan('birthday:send')->assertExitCode(0);

        $this->assertCount(0, $pengirim->kirim);
        $this->assertDatabaseCount('pesan_ulang_tahun', 0);
    }

    public function test_already_sent_message_is_not_sent_again(): void
    {
        $pengirim = $this->fakePengirim();
        $this->birthdayCustomer();

        $this->artisan('birthday:send')->assertExitCode(0);
        $this->artisan('birthday:send')->assertExitCode(0);

        $this->assertCount(1, $pengirim->kirim);
        $this->assertDatabaseCount('pesan_ulang_tahun', 1);
        $this->assertDatabaseCount('hampers', 1);
    }

    public function test_failed_send_is_recorded_as_gagal(): void
    {
        $pengirim = $this->fakePengirim();
        $pengirim->gagal = new RuntimeException('Gateway sedang down');
        $this->birthdayCustomer();

        $this->artisan('birthday:send')->assertExitCode(0);

        $pesan = PesanUlangTahun::query()->sole();
        $this->assertSame(PesanUlangTahun::STATUS_GAGAL, $pesan->status);
        $this->assertStringContainsString('Gateway sedang down', $pesan->error);
        $this->assertDatabaseCount('hampers', 0);
    }

    public function test_eligible_member_without_quota_gets_greeting_only(): void
    {
        $pengirim = $this->fakePengirim();
        KuotaHampers::create(['tahun' => now()->year, 'kuota_maks' => 1]);

        $terpakai = $this->birthdayCustomer(['nama' => 'Fajar', 'no_wa' => '628566666666']);
        $idPesan = DB::table('pesan_ulang_tahun')->insertGetId([
            'id_customer' => $terpakai->id_customer,
            'tahun' => now()->year,
            'jenis' => 'HAMPERS',
            'status' => 'TERKIRIM',
        ]);
        Hampers::create([
            'id_customer' => $terpakai->id_customer,
            'id_pesan' => $idPesan,
            'tahun_kuota' => now()->year,
            'kode_klaim' => 'HAM-TAK001',
            'batas_ambil' => now()->addDays(14)->toDateString(),
            'status' => 'BELUM_DIAMBIL',
        ]);

        $this->birthdayCustomer(['nama' => 'Galih', 'no_wa' => '628577777777']);

        $this->artisan('birthday:send')->assertExitCode(0);

        $galih = PesanUlangTahun::query()->whereHas('customer', fn ($q) => $q->where('nama', 'Galih'))->sole();
        $this->assertSame(PesanUlangTahun::JENIS_BIASA, $galih->jenis);
        $this->assertCount(1, $pengirim->kirim);
        $this->assertDatabaseCount('hampers', 1);
    }

    public function test_min_visits_can_be_lowered_from_settings(): void
    {
        Pengaturan::simpan('min_visits', '2');
        $this->fakePengirim();
        $this->birthdayCustomer(['nama' => 'Hana', 'no_wa' => '628588888888'], 2);

        $this->artisan('birthday:send')->assertExitCode(0);

        $this->assertSame(PesanUlangTahun::JENIS_HAMPERS, PesanUlangTahun::query()->sole()->jenis);
    }

    public function test_command_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('birthday:send')
            ->assertExitCode(0);
    }
}
