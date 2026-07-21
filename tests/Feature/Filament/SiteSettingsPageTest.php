<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_is_limited_to_administrators(): void
    {
        $this->get(SiteSettings::getUrl())->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get(SiteSettings::getUrl())->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(SiteSettings::getUrl())->assertOk();
    }

    public function test_empty_table_creates_one_singleton_and_repeated_access_reuses_it(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get(SiteSettings::getUrl())->assertOk();
        $this->get(SiteSettings::getUrl())->assertOk();
        $this->assertSame(1, SiteSetting::query()->count());
        $this->assertSame(config('app.name'), SiteSetting::query()->firstOrFail()->site_name);
    }

    public function test_existing_record_is_updated_without_creating_another_or_changing_media(): void
    {
        $setting = SiteSetting::factory()->create(['logo_path' => 'logo.webp', 'favicon_path' => 'favicon.webp']);
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(SiteSettings::class)->fillForm($this->validData())->call('save')->assertHasNoFormErrors()
            ->assertNotified('Pengaturan situs berhasil disimpan.');
        $setting->refresh();
        $this->assertSame('KUPAT Bekasi Baru', $setting->site_name);
        $this->assertSame('logo.webp', $setting->logo_path);
        $this->assertSame('favicon.webp', $setting->favicon_path);
        $this->assertSame(1, SiteSetting::query()->count());
    }

    public function test_name_and_text_limits_are_validated(): void
    {
        $this->assertFieldError('site_name', null, 'required');
        $this->assertFieldError('site_name', str_repeat('A', 256), 'max');
        $this->assertFieldError('about_summary', str_repeat('A', 2001), 'max');
    }

    public function test_whatsapp_validation(): void
    {
        $this->assertFieldAccepted('contact_whatsapp', '628000000000');
        foreach (['0800000000', '62abc', '62 8000', '+628000000000'] as $value) {
            $this->assertFieldError('contact_whatsapp', $value);
        }
    }

    public function test_email_validation(): void
    {
        $this->assertFieldAccepted('contact_email', 'admin@example.test');
        $this->assertFieldError('contact_email', 'bukan-email', 'email');
    }

    public function test_instagram_url_validation(): void
    {
        $this->assertFieldAccepted('instagram_url', 'http://example.test/profil');
        $this->assertFieldAccepted('instagram_url', 'https://example.test/profil');
        foreach (['/profil', 'profil', 'javascript:alert(1)', 'data:text/plain,test', '//example.test'] as $value) {
            $this->assertFieldError('instagram_url', $value);
        }
    }

    public function test_instagram_url_length_matches_the_expanded_schema(): void
    {
        $url = 'https://example.test/'.str_repeat('a', 2027);
        $this->assertSame(2048, strlen($url));
        $this->assertFieldAccepted('instagram_url', $url);
        $this->assertSame($url, SiteSetting::query()->firstOrFail()->instagram_url);
        $this->assertFieldError('instagram_url', $url.'a', 'max');
    }

    public function test_instagram_length_migration_can_be_rolled_back_and_run_again(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();
        $this->artisan('migrate')->assertSuccessful();
    }

    public function test_no_crud_routes_are_registered(): void
    {
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.site-settings.index'));
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.site-settings.create'));
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.site-settings.edit'));
    }

    public function test_demo_seed_keeps_one_site_setting(): void
    {
        $this->seed();
        $this->assertSame(1, SiteSetting::query()->count());
    }

    private function assertFieldError(string $field, mixed $value, ?string $rule = null): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(SiteSettings::class)->fillForm(array_merge($this->validData(), [$field => $value]))
            ->call('save')->assertHasFormErrors([$field => $rule]);
    }

    private function assertFieldAccepted(string $field, mixed $value): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(SiteSettings::class)->fillForm(array_merge($this->validData(), [$field => $value]))
            ->call('save')->assertHasNoFormErrors();
    }

    /** @return array<string, mixed> */
    private function validData(): array
    {
        return ['site_name' => 'KUPAT Bekasi Baru', 'tagline' => 'Tagline', 'about_summary' => 'Ringkasan',
            'contact_whatsapp' => '628000000000', 'contact_email' => 'admin@example.test',
            'address' => 'Bekasi', 'instagram_url' => 'https://example.test/profil'];
    }
}
