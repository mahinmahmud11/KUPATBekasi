<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $categorySlug = trim((string) $request->query('category'));
        $partnerSlug = trim((string) $request->query('partner'));

        $products = Product::query()
            ->active()
            ->whereHas('partner', fn (Builder $query) => $query->active())
            ->whereHas('category', fn (Builder $query) => $query->active())
            ->with(['partner', 'category'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%")
                        ->orWhereHas('partner', fn (Builder $partnerQuery) => $partnerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($categorySlug !== '', fn (Builder $query) => $query->whereHas(
                'category',
                fn (Builder $categoryQuery) => $categoryQuery->where('slug', $categorySlug),
            ))
            ->when($partnerSlug !== '', fn (Builder $query) => $query->whereHas(
                'partner',
                fn (Builder $partnerQuery) => $partnerQuery->where('slug', $partnerSlug),
            ))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->active()->ordered()->get();
        $partners = Partner::query()->active()->ordered()->get();

        return view('products.index', compact('categories', 'categorySlug', 'partners', 'partnerSlug', 'products', 'search'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category',
            'images' => fn ($query) => $query->ordered(),
            'partner',
        ]);

        abort_unless($product->partner?->is_active && $product->category?->is_active, 404);

        $relatedProducts = Product::query()
            ->active()
            ->whereKeyNot($product->getKey())
            ->where(function (Builder $query) use ($product): void {
                $query
                    ->where('category_id', $product->category_id)
                    ->orWhere('partner_id', $product->partner_id);
            })
            ->whereHas('partner', fn (Builder $query) => $query->active())
            ->whereHas('category', fn (Builder $query) => $query->active())
            ->with(['partner', 'category'])
            ->ordered()
            ->limit(4)
            ->get();

        $whatsappUrl = $this->whatsappUrl($product);

        return view('products.show', compact('product', 'relatedProducts', 'whatsappUrl'));
    }

    private function whatsappUrl(Product $product): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $product->partner->whatsapp);

        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        }

        if (! preg_match('/^62\d{8,13}$/', $number)) {
            return null;
        }

        $message = "Halo, saya melihat produk {$product->name} di KUPATBekasi. Apakah produk ini masih tersedia?";

        return 'https://wa.me/'.$number.'?'.http_build_query(['text' => $message], encoding_type: PHP_QUERY_RFC3986);
    }
}
