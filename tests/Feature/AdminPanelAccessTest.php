<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_admin_login_page(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_regular_user_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_administrator_can_access_the_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_is_admin_is_cast_to_boolean_and_defaults_to_false(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->assertIsBool($user->is_admin);
        $this->assertFalse($user->is_admin);
        $this->assertIsBool($admin->is_admin);
        $this->assertTrue($admin->is_admin);
    }

    public function test_public_registration_route_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_is_admin_migration_can_be_rolled_back_and_run_again(): void
    {
        $migration = require database_path('migrations/2026_07_21_000001_add_is_admin_to_users_table.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('users', 'is_admin'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn('users', 'is_admin'));
    }
}
