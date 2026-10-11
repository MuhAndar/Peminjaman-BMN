<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $categories = AssetCategory::all();
        $selectedCategory = $request->query('category');
        $search = $request->query('search');

        $query = Asset::with('category')->orderBy('item_name', 'asc');

        if ($selectedCategory) {
            $query->where('category_code', $selectedCategory);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%")
                    ->orWhere('nup', 'like', "%{$search}%");
            });
        }

        $assets = $query->paginate(12)->withQueryString();

        return view('assets.index', compact('assets', 'categories', 'selectedCategory', 'search'));
    }

    public function show(Asset $asset)
    {
        $asset->load(['category', 'loanItems.loanRequest.borrower']);
        return view('assets.show', compact('asset'));
    }
}
