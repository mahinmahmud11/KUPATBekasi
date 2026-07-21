<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_name',
        'tagline',
        'about_summary',
        'contact_whatsapp',
        'contact_email',
        'address',
        'instagram_url',
        'logo_path',
        'favicon_path',
    ];

    protected static function booted(): void
    {
        static::updated(function (SiteSetting $setting) {
            if ($setting->wasChanged('logo_path')) {
                $oldPath = $setting->getPrevious()['logo_path'] ?? null;
                if ($oldPath && $oldPath !== $setting->logo_path) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            if ($setting->wasChanged('favicon_path')) {
                $oldPath = $setting->getPrevious()['favicon_path'] ?? null;
                if ($oldPath && $oldPath !== $setting->favicon_path) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        });

        static::deleted(function (SiteSetting $setting) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            if ($setting->favicon_path) {
                Storage::disk('public')->delete($setting->favicon_path);
            }
        });
    }
}
