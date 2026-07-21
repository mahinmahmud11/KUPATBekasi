<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class CategoryController extends Controller
{
    public function show(Category $category): View
    {
        abort_unless($category->is_active, 404);

        $products = $category->products()
            ->active()
            ->whereHas('partner', fn (Builder $query) => $query->active())
            ->with(['partner', 'category'])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(12);

        return view('categories.show', compact('category', 'products'));
    }
}
