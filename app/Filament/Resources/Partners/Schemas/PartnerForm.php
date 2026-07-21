<?php

namespace App\Filament\Resources\Partners\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Usaha')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(fn (string $operation): bool => $operation === 'create')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                        if (blank($get('slug'))) {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    }),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('owner_name')
                    ->label('Nama Pemilik')
                    ->maxLength(255),
                Textarea::make('short_description')
                    ->label('Deskripsi Singkat')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Deskripsi Lengkap')
                    ->columnSpanFull(),
                Textarea::make('address')
                    ->label('Alamat')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('district')
                    ->label('Kecamatan/Wilayah')
                    ->required()
                    ->maxLength(255),
                TextInput::make('whatsapp')
                    ->label('WhatsApp')
                    ->required()
                    ->regex('/^62[0-9]{8,13}$/')
                    ->maxLength(15)
                    ->helperText('Gunakan format internasional tanpa tanda +, contoh: 628000000000.'),
                TextInput::make('instagram_url')
                    ->label('Instagram')
                    ->url()
                    ->maxLength(2048),
                Toggle::make('is_featured')
                    ->label('Mitra Unggulan')
                    ->default(false),
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
