<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
