<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(fn (string $operation): bool => $operation === 'create'),
                Textarea::make('subtitle')
                    ->label('Subjudul')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                TextInput::make('button_label')
                    ->label('Teks Tombol')
                    ->maxLength(100)
                    ->requiredWith('button_url'),
                TextInput::make('button_url')
                    ->label('Tautan Tombol')
                    ->maxLength(2048)
                    ->regex('#^(?:/(?!/)[^\\s]*|https?://[^\\s]+)$#i')
                    ->requiredWith('button_label')
                    ->helperText('Gunakan path internal seperti /produk atau URL lengkap https://...'),
                FileUpload::make('image_path')
                    ->label('Gambar Banner')
                    ->image()
                    ->disk('public')
                    ->directory('banners')
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
            ])
            ->columns([
                'default' => 1,
                'md' => 2,
            ]);
    }
}
