<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
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

    public function test_password_is_stored_hashed(): void
    {
        $admin = $this->makeAdmin();

        $this->assertNotSame('rahasia123', $admin->fresh()->password);
        $this->assertTrue(Hash::check('rahasia123', $admin->fresh()->password));
    }

    public function test_login_page_is_rendered(): void
    {
        $this->get('/login')->assertOk()->assertSee('Admin Angkringan');
    }

    public function test_admin_can_login(): void
    {
        $admin = $this->makeAdmin();

        $this->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'rahasia123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeAdmin();

        $this->from('/login')->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'salah',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_guest_is_redirected_from_admin_area(): void
    {
        $this->get('/admin/customers')->assertRedirect(route('login'));
    }

    public function test_admin_can_logout(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->post('/admin/logout')->assertRedirect(route('login'));

        $this->assertGuest('admin');
    }
}
