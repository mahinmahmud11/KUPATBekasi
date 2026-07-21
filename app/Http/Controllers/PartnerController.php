<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $partners = Partner::query()
            ->active()
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->withCount(['products' => fn ($query) => $query->active()])
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        return view('partners.index', compact('partners', 'search'));
    }

    public function show(Partner $partner): View
    {
        abort_unless($partner->is_active, 404);

        $products = $partner->products()
            ->active()
            ->whereHas('category', fn (Builder $query) => $query->active())
            ->with(['partner', 'category'])
            ->ordered()
            ->paginate(12);

        return view('partners.show', compact('partner', 'products'));
    }
}
