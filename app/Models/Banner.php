<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'button_label',
        'button_url',
        'is_active',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::updated(function (Banner $banner) {
            if (! $banner->wasChanged('image_path')) {
                return;
            }

            $previousImagePath = $banner->getPrevious()['image_path'] ?? null;

            if ($previousImagePath && $previousImagePath !== $banner->image_path) {
                Storage::disk('public')->delete($previousImagePath);
            }
        });

        static::deleted(function (Banner $banner) {
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
