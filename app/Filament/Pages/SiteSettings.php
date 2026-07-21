<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Pengaturan Situs';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.site-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public SiteSetting $settings;

    public function mount(): void
    {
        $this->settings = SiteSetting::query()->firstOrCreate([], ['site_name' => config('app.name')]);
        $this->form->fill($this->settings->only($this->fields()));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('site_name')->label('Nama Situs')->required()->maxLength(255)->autofocus(),
            TextInput::make('tagline')->label('Tagline')->maxLength(255),
            Textarea::make('about_summary')->label('Ringkasan Tentang')->maxLength(2000)->columnSpanFull(),
            TextInput::make('contact_whatsapp')->label('WhatsApp')->regex('/^62[0-9]{8,13}$/')->maxLength(15)
                ->validationMessages([
                    'regex' => 'Nomor WhatsApp harus diawali 62 dan hanya berisi angka.',
                ])
                ->helperText('Gunakan nomor dummy berformat 628000000000.'),
            TextInput::make('contact_email')->label('Email')->email()->maxLength(255),
            Textarea::make('address')->label('Alamat')->maxLength(2000)->columnSpanFull(),
            TextInput::make('instagram_url')->label('Instagram')->maxLength(2048)
                ->regex('#^https?://[^\\s]+$#i')
                ->validationMessages([
                    'regex' => 'Instagram harus berupa URL lengkap yang diawali http:// atau https://.',
                ]),
            FileUpload::make('logo_path')
                ->label('Logo Situs')
                ->image()
                ->disk('public')
                ->directory('site-settings/logos')
                ->maxSize(2048)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->columnSpanFull(),
            FileUpload::make('favicon_path')
                ->label('Favicon')
                ->image()
                ->disk('public')
                ->directory('site-settings/favicons')
                ->maxSize(512)
                ->acceptedFileTypes(['image/png', 'image/webp'])
                ->helperText('Gunakan gambar persegi PNG atau WebP, maksimal 512 KB.')
                ->columnSpanFull(),
        ])->columns(['default' => 1, 'md' => 2])->statePath('data');
    }

    public function save(): void
    {
        $this->settings->update($this->form->getState());
        Notification::make()->success()->title('Pengaturan situs berhasil disimpan.')->send();
    }

    public function getTitle(): string
    {
        return 'Pengaturan Situs';
    }

    /** @return array<int, string> */
    private function fields(): array
    {
        return ['site_name', 'tagline', 'about_summary', 'contact_whatsapp', 'contact_email', 'address', 'instagram_url', 'logo_path', 'favicon_path'];
    }
}
