<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\KuotaHampers;
use App\Support\Pengaturan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            @unlink($file);
        }

        $this->createdFiles = [];

        parent::tearDown();
    }

    private function makeAdmin(): Admin
    {
        return Admin::create([
            'nama' => 'Admin Uji',
            'email' => 'admin@example.test',
            'password' => 'rahasia123',
        ]);
    }

    public function test_guest_is_redirected_from_settings_page(): void
    {
        $this->get('/admin/settings')->assertRedirect(route('login'));
    }

    public function test_settings_page_is_rendered_with_defaults(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin')
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Pengaturan Program')
            ->assertSee('Syarat birthday hampers')
            ->assertSee('Brosur program')
            ->assertSee('name="min_visits"', false)
            ->assertSee('name="window_days"', false)
            ->assertSee('name="annual_quota"', false)
            ->assertViewHas('minVisits', Pengaturan::DEFAULT_MIN_VISITS)
            ->assertViewHas('windowDays', Pengaturan::DEFAULT_WINDOW_DAYS)
            ->assertViewHas('quotaYear', 100)
            ->assertViewHas('brochures', fn ($brochures) => count($brochures) === 2);
    }

    public function test_settings_page_uses_correct_default_brochure_images(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin')
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('images/brosur-poster.jpeg', false)
            ->assertSee('images/brosur-voucher.jpeg', false)
            ->assertDontSee('assets/img/brosur-poster.jpg', false);
    }

    public function test_settings_page_shows_saved_values(): void
    {
        Pengaturan::simpan('min_visits', '12');
        Pengaturan::simpan('window_days', '30');
        KuotaHampers::create(['tahun' => now()->year, 'kuota_maks' => 40]);

        $this->actingAs($this->makeAdmin(), 'admin')
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertViewHas('minVisits', 12)
            ->assertViewHas('windowDays', 30)
            ->assertViewHas('quotaYear', 40);
    }

    public function test_settings_can_be_saved(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin')
            ->put(route('admin.settings.update'), [
                'min_visits' => 8,
                'window_days' => 60,
                'annual_quota' => 25,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertEquals('Parameter program berhasil disimpan.', session('status'));
        $this->assertSame(8, Pengaturan::ambilInt('min_visits'));
        $this->assertSame(60, Pengaturan::ambilInt('window_days'));
        $this->assertDatabaseHas('kuota_hampers', [
            'tahun' => now()->year,
            'kuota_maks' => 25,
        ]);
    }

    public function test_saving_quota_updates_existing_row_instead_of_duplicating(): void
    {
        KuotaHampers::create(['tahun' => now()->year, 'kuota_maks' => 40]);
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')->put(route('admin.settings.update'), [
            'min_visits' => 5,
            'window_days' => 90,
            'annual_quota' => 77,
        ])->assertRedirect(route('admin.settings.edit'));

        $this->assertSame(1, KuotaHampers::query()->where('tahun', now()->year)->count());
        $this->assertSame(77, KuotaHampers::tahun(now()->year));
    }

    public function test_settings_validation_rejects_out_of_range_values(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin')
            ->putJson(route('admin.settings.update'), [
                'min_visits' => 0,
                'window_days' => 999,
                'annual_quota' => -1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['min_visits', 'window_days', 'annual_quota']);

        $this->assertNull(Pengaturan::ambil('min_visits'));
    }

    public function test_brochure_image_can_be_uploaded(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.brochure'), [
                'brochure_main' => UploadedFile::fake()->image('poster.png', 20, 20),
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertEquals('Brosur program berhasil diperbarui.', session('status'));

        $tersimpan = Pengaturan::ambil('brochure_main');
        $this->assertNotNull($tersimpan);

        $path = public_path('images/brosur/'.$tersimpan);
        $this->assertFileExists($path);
        $this->createdFiles[] = $path;

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('images/brosur/'.$tersimpan, false);
    }

    public function test_brochure_upload_rejects_wrong_file_type(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin')
            ->postJson(route('admin.settings.brochure'), [
                'brochure_main' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('brochure_main');

        $this->assertNull(Pengaturan::ambil('brochure_main'));
    }

    public function test_brochure_upload_requires_at_least_one_file(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin')
            ->postJson(route('admin.settings.brochure'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['brochure_main', 'brochure_voucher']);
    }

    public function test_birthday_page_reads_values_from_settings(): void
    {
        Pengaturan::simpan('min_visits', '7');
        Pengaturan::simpan('window_days', '45');
        KuotaHampers::create(['tahun' => now()->year, 'kuota_maks' => 30]);

        $this->actingAs($this->makeAdmin(), 'admin')
            ->get(route('admin.birthday.index'))
            ->assertOk()
            ->assertViewHas('minVisits', 7)
            ->assertViewHas('windowDays', 45)
            ->assertViewHas('quotaYear', 30)
            ->assertSee('minimal 7 kunjungan dine-in dalam 45 hari', false);
    }
}
