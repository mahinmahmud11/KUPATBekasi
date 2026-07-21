<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_singleton_kosong_dapat_menyimpan_logo_dan_favicon(): void
    {
        Storage::fake('public');
        $logo = UploadedFile::fake()->image('logo.webp')->size(1024);
        $favicon = UploadedFile::fake()->image('favicon.webp')->size(256);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(SiteSettings::class)
            ->fillForm(array_merge($this->validData(), ['logo_path' => [$logo], 'favicon_path' => [$favicon]]))
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(1, SiteSetting::query()->count());
        $setting = SiteSetting::query()->firstOrFail();

        $this->assertStringStartsWith('site-settings/logos/', $setting->logo_path);
        $this->assertStringStartsWith('site-settings/favicons/', $setting->favicon_path);

        Storage::disk('public')->assertExists($setting->logo_path);
        Storage::disk('public')->assertExists($setting->favicon_path);
    }

    public function test_logo_dan_favicon_non_gambar_ditolak(): void
    {
        Storage::fake('public');
        $this->assertFieldError('logo_path', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'));
        $this->assertFieldError('favicon_path', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'));
    }

    public function test_logo_di_atas_2_mb_ditolak(): void
    {
        Storage::fake('public');
        $this->assertFieldError('logo_path', UploadedFile::fake()->image('logo.jpg')->size(2049));
    }

    public function test_favicon_di_atas_512_kb_ditolak(): void
    {
        Storage::fake('public');
        $this->assertFieldError('favicon_path', UploadedFile::fake()->image('favicon.png')->size(513));
    }

    public function test_penggantian_logo_melalui_form_logo_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('site-settings/logos/old.webp', 'old');
        Storage::disk('public')->put('site-settings/favicons/fav.webp', 'fav');

        SiteSetting::factory()->create(['logo_path' => 'site-settings/logos/old.webp', 'favicon_path' => 'site-settings/favicons/fav.webp']);

        $newFile = UploadedFile::fake()->image('new.jpg')->size(1024);

        $this->actingAs(User::factory()->admin()->create());
        $component = Livewire::test(SiteSettings::class);
        $component->fillForm($this->validData());

        $state = $component->get('data.logo_path');
        $this->assertIsArray($state);
        $fileKey = array_search('site-settings/logos/old.webp', $state, true);
        $this->assertNotFalse($fileKey);

        $component->call('callSchemaComponentMethod', 'form.logo_path', 'deleteUploadedFile', ['fileKey' => $fileKey]);
        $component->set('data.logo_path', [$newFile]);
        $component->call('save')->assertHasNoFormErrors();

        $setting = SiteSetting::query()->firstOrFail();
        $this->assertNotSame('site-settings/logos/old.webp', $setting->logo_path);
        $this->assertStringStartsWith('site-settings/logos/', $setting->logo_path);

        Storage::disk('public')->assertMissing('site-settings/logos/old.webp');
        Storage::disk('public')->assertExists($setting->logo_path);

        $this->assertSame('site-settings/favicons/fav.webp', $setting->favicon_path);
        Storage::disk('public')->assertExists('site-settings/favicons/fav.webp');
    }

    public function test_penggantian_favicon_melalui_form_favicon_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('site-settings/logos/logo.webp', 'logo');
        Storage::disk('public')->put('site-settings/favicons/old.webp', 'old');

        SiteSetting::factory()->create(['logo_path' => 'site-settings/logos/logo.webp', 'favicon_path' => 'site-settings/favicons/old.webp']);

        $newFile = UploadedFile::fake()->image('new.png')->size(256);

        $this->actingAs(User::factory()->admin()->create());
        $component = Livewire::test(SiteSettings::class);
        $component->fillForm($this->validData());

        $state = $component->get('data.favicon_path');
        $this->assertIsArray($state);
        $fileKey = array_search('site-settings/favicons/old.webp', $state, true);
        $this->assertNotFalse($fileKey);

        $component->call('callSchemaComponentMethod', 'form.favicon_path', 'deleteUploadedFile', ['fileKey' => $fileKey]);
        $component->set('data.favicon_path', [$newFile]);
        $component->call('save')->assertHasNoFormErrors();

        $setting = SiteSetting::query()->firstOrFail();
        $this->assertNotSame('site-settings/favicons/old.webp', $setting->favicon_path);
        $this->assertStringStartsWith('site-settings/favicons/', $setting->favicon_path);

        Storage::disk('public')->assertMissing('site-settings/favicons/old.webp');
        Storage::disk('public')->assertExists($setting->favicon_path);

        $this->assertSame('site-settings/logos/logo.webp', $setting->logo_path);
        Storage::disk('public')->assertExists('site-settings/logos/logo.webp');
    }

    public function test_delete_melalui_eloquent_menghapus_kedua_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('site-settings/logos/logo.webp', 'logo');
        Storage::disk('public')->put('site-settings/favicons/fav.webp', 'fav');

        $setting = SiteSetting::factory()->create(['logo_path' => 'site-settings/logos/logo.webp', 'favicon_path' => 'site-settings/favicons/fav.webp']);

        $setting->delete();

        Storage::disk('public')->assertMissing('site-settings/logos/logo.webp');
        Storage::disk('public')->assertMissing('site-settings/favicons/fav.webp');
    }

    public function test_delete_aman_untuk_path_null_atau_file_hilang(): void
    {
        Storage::fake('public');
        $setting = SiteSetting::factory()->create(['logo_path' => null, 'favicon_path' => 'site-settings/favicons/missing.webp']);

        $setting->delete();
        $this->assertSame(0, SiteSetting::query()->count());
    }

    public function test_existing_record_is_updated_without_creating_another_or_changing_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('site-settings/logos/logo.webp', 'logo content');
        Storage::disk('public')->put('site-settings/favicons/favicon.webp', 'favicon content');

        $setting = SiteSetting::factory()->create(['logo_path' => 'site-settings/logos/logo.webp', 'favicon_path' => 'site-settings/favicons/favicon.webp']);
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(SiteSettings::class)->fillForm($this->validData())->call('save')->assertHasNoFormErrors()
            ->assertNotified('Pengaturan situs berhasil disimpan.');
        $setting->refresh();
        $this->assertSame('KUPAT Bekasi Baru', $setting->site_name);
        $this->assertSame('site-settings/logos/logo.webp', $setting->logo_path);
        $this->assertSame('site-settings/favicons/favicon.webp', $setting->favicon_path);
        $this->assertSame(1, SiteSetting::query()->count());

        Storage::disk('public')->assertExists('site-settings/logos/logo.webp');
        Storage::disk('public')->assertExists('site-settings/favicons/favicon.webp');
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
            $this->assertFieldError('contact_whatsapp', $value, 'regex');
        }

        $this->actingAs(User::factory()->admin()->create());
        $component = Livewire::test(SiteSettings::class)->fillForm(array_merge($this->validData(), ['contact_whatsapp' => '0800000000']))
            ->call('save')->assertHasErrors(['data.contact_whatsapp']);

        $this->assertSame('Nomor WhatsApp harus diawali 62 dan hanya berisi angka.', $component->errors()->first('data.contact_whatsapp'));
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
            $this->assertFieldError('instagram_url', $value, 'regex');
        }

        $this->actingAs(User::factory()->admin()->create());
        $component = Livewire::test(SiteSettings::class)->fillForm(array_merge($this->validData(), ['instagram_url' => 'profil']))
            ->call('save')->assertHasErrors(['data.instagram_url']);

        $this->assertSame('Instagram harus berupa URL lengkap yang diawali http:// atau https://.', $component->errors()->first('data.instagram_url'));
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
