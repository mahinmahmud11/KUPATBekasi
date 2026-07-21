<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;

class InformationPageController extends Controller
{
    public function about(): View
    {
        return view('pages.about', ['siteSetting' => SiteSetting::query()->first()]);
    }

    public function contact(): View
    {
        return view('pages.contact', ['siteSetting' => SiteSetting::query()->first()]);
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }
}
