<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_new_admin_user(): void
    {
        $initialCount = User::count();

        $this->artisan('kupat:make-admin')
            ->expectsQuestion('Alamat Email', 'admin@example.com')
            ->expectsOutputToContain('Membuat akun administrator baru.')
            ->expectsQuestion('Nama Lengkap', 'Admin Baru')
            ->expectsQuestion('Password (minimal 8 karakter)', 'password123')
            ->expectsQuestion('Konfirmasi Password', 'password123')
            ->expectsOutputToContain('berhasil dibuat')
            ->assertSuccessful();

        $this->assertEquals($initialCount + 1, User::count());
        $user = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Admin Baru', $user->name);
        $this->assertTrue($user->is_admin);

        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_promotes_existing_regular_user(): void
    {
        $user = User::factory()->create([
            'name' => 'User Biasa',
            'email' => 'user@example.com',
            'is_admin' => false,
            'password' => Hash::make('oldpassword'),
        ]);

        $initialCount = User::count();
        $initialPassword = $user->password;

        $this->artisan('kupat:make-admin')
            ->expectsQuestion('Alamat Email', 'user@example.com')
            ->expectsOutputToContain('sudah terdaftar')
            ->expectsConfirmation("Apakah Anda ingin mempromosikan akun {$user->name} menjadi administrator?", 'yes')
            ->expectsOutputToContain('berhasil dipromosikan')
            ->assertSuccessful();

        $this->assertEquals($initialCount, User::count());

        $user->refresh();
        $this->assertTrue($user->is_admin);
        $this->assertEquals('User Biasa', $user->name);
        $this->assertEquals('user@example.com', $user->email);
        $this->assertEquals($initialPassword, $user->password);
    }

    public function test_does_not_promote_if_cancelled_or_default_no(): void
    {
        $user = User::factory()->create([
            'name' => 'User Ragu',
            'email' => 'user@example.com',
            'is_admin' => false,
            'password' => Hash::make('oldpassword'),
        ]);

        $initialCount = User::count();
        $initialPassword = $user->password;

        $this->artisan('kupat:make-admin')
            ->expectsQuestion('Alamat Email', 'user@example.com')
            ->expectsOutputToContain('sudah terdaftar')
            ->expectsConfirmation("Apakah Anda ingin mempromosikan akun {$user->name} menjadi administrator?", 'no')
            ->expectsOutputToContain('Promosi dibatalkan')
            ->assertSuccessful();

        $this->assertEquals($initialCount, User::count());

        $user->refresh();
        $this->assertFalse($user->is_admin);
        $this->assertEquals('User Ragu', $user->name);
        $this->assertEquals('user@example.com', $user->email);
        $this->assertEquals($initialPassword, $user->password);
    }

    public function test_idempotent_if_already_admin(): void
    {
        $user = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'is_admin' => true,
            'password' => Hash::make('adminpassword'),
        ]);

        $initialCount = User::count();
        $initialPassword = $user->password;

        $this->artisan('kupat:make-admin')
            ->expectsQuestion('Alamat Email', 'admin@example.com')
            ->expectsOutputToContain('sudah terdaftar')
            ->expectsOutputToContain('sudah memiliki hak akses administrator')
            ->assertSuccessful();

        $this->assertEquals($initialCount, User::count());

        $user->refresh();
        $this->assertTrue($user->is_admin);
        $this->assertEquals('Super Admin', $user->name);
        $this->assertEquals('admin@example.com', $user->email);
        $this->assertEquals($initialPassword, $user->password);
    }

    public function test_normalizes_input_email_and_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Spaced User',
            'email' => 'spaced@example.com',
            'is_admin' => false,
        ]);

        $this->artisan('kupat:make-admin')
            ->expectsQuestion('Alamat Email', '  spaced@example.com  ')
            ->expectsOutputToContain('sudah terdaftar')
            ->expectsConfirmation("Apakah Anda ingin mempromosikan akun {$user->name} menjadi administrator?", 'yes')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('kupat:make-admin')
            ->expectsQuestion('Alamat Email', ' new@example.com ')
            ->expectsQuestion('Nama Lengkap', '   Admin Spasi   ')
            ->expectsQuestion('Password (minimal 8 karakter)', 'password123')
            ->expectsQuestion('Konfirmasi Password', 'password123')
            ->assertSuccessful();

        $newUser = User::where('email', 'new@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Admin Spasi', $newUser->name);
    }

    public function test_fails_on_invalid_email(): void
    {
        $this->artisan('kupat:make-admin')
            ->expectsQuestion('Alamat Email', 'not-an-email')
            ->expectsOutputToContain('Email tidak valid')
            ->assertFailed();
    }

    public function test_fails_on_empty_name(): void
    {
        $this->artisan('kupat:make-admin')
            ->expectsQuestion('Alamat Email', 'new@example.com')
            ->expectsQuestion('Nama Lengkap', '   ')
            ->expectsOutputToContain('Nama wajib diisi')
            ->assertFailed();
    }

    public function test_fails_on_invalid_password(): void
    {
        $this->artisan('kupat:make-admin')
            ->expectsQuestion('Alamat Email', 'new@example.com')
            ->expectsQuestion('Nama Lengkap', 'Name')
            ->expectsQuestion('Password (minimal 8 karakter)', 'short')
            ->expectsQuestion('Konfirmasi Password', 'short')
            ->expectsOutputToContain('Validasi password gagal')
            ->assertFailed();
    }
}
