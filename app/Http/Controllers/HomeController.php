<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $heroBanner = Banner::query()
            ->active()
            ->ordered()
            ->first();

        $categories = Category::query()->active()->ordered()->limit(6)->get();

        $featuredProducts = Product::query()
            ->active()
            ->featured()
            ->whereHas('partner', fn ($query) => $query->active())
            ->whereHas('category', fn ($query) => $query->active())
            ->with(['partner', 'category'])
            ->ordered()
            ->limit(8)
            ->get();

        $featuredPartners = Partner::query()
            ->active()
            ->featured()
            ->withCount(['products' => fn ($query) => $query->active()])
            ->ordered()
            ->limit(6)
            ->get();

        $latestProducts = Product::query()
            ->active()
            ->whereHas('partner', fn ($query) => $query->active())
            ->whereHas('category', fn ($query) => $query->active())
            ->with(['partner', 'category'])
            ->latest()
            ->limit(8)
            ->get();

        $aboutSummary = SiteSetting::query()->value('about_summary');

        return view('home', compact(
            'aboutSummary',
            'categories',
            'featuredPartners',
            'featuredProducts',
            'heroBanner',
            'latestProducts',
        ));
    }
}
