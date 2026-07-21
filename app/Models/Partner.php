<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Partner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'owner_name',
        'short_description',
        'description',
        'address',
        'district',
        'whatsapp',
        'instagram_url',
        'logo_path',
        'cover_path',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    protected static function booted(): void
    {
        static::updated(function (Partner $partner) {
            if ($partner->wasChanged('logo_path')) {
                $previousLogoPath = $partner->getPrevious()['logo_path'] ?? null;
                if ($previousLogoPath && $previousLogoPath !== $partner->logo_path) {
                    Storage::disk('public')->delete($previousLogoPath);
                }
            }

            if ($partner->wasChanged('cover_path')) {
                $previousCoverPath = $partner->getPrevious()['cover_path'] ?? null;
                if ($previousCoverPath && $previousCoverPath !== $partner->cover_path) {
                    Storage::disk('public')->delete($previousCoverPath);
                }
            }
        });

        static::forceDeleted(function (Partner $partner) {
            if ($partner->logo_path) {
                Storage::disk('public')->delete($partner->logo_path);
            }
            if ($partner->cover_path) {
                Storage::disk('public')->delete($partner->cover_path);
            }
        });
    }
}
