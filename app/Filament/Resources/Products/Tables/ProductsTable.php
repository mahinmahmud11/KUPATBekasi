<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('partner.name')
                    ->label('Mitra')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable(['name', 'slug']),
                TextColumn::make('price')
                    ->label('Harga')
                    ->formatStateUsing(fn (int $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Satuan'),
                TextColumn::make('stock_status')
                    ->label('Status Stok')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Tersedia',
                        'preorder' => 'Pre-order',
                        'unavailable' => 'Tidak Tersedia',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'preorder' => 'warning',
                        'unavailable' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('is_featured')
                    ->label('Unggulan')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name', fn (Builder $query): Builder => $query->orderBy('name'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('partner_id')
                    ->label('Mitra')
                    ->relationship('partner', 'name', fn (Builder $query): Builder => $query->orderBy('name'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('stock_status')
                    ->label('Status Stok')
                    ->options([
                        'available' => 'Tersedia',
                        'preorder' => 'Pre-order',
                        'unavailable' => 'Tidak Tersedia',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->placeholder('Semua'),
                TernaryFilter::make('is_featured')
                    ->label('Produk Unggulan')
                    ->trueLabel('Unggulan')
                    ->falseLabel('Biasa')
                    ->placeholder('Semua'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah'),
                DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->emptyStateHeading('Belum ada produk');
    }
}
