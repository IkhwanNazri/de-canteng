<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\SaveProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $shop = $request->user()->shop;

        return Inertia::render('Seller/Products/Index', [
            'shop' => $shop->only('id', 'name', 'slug'),
            'products' => $shop->products()->with('category:id,name')->latest()->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Seller/Products/Form', [
            'product' => null,
            'categories' => Category::orderBy('sort')->get(['id', 'name']),
        ]);
    }

    public function store(SaveProductRequest $request): RedirectResponse
    {
        $request->user()->shop->products()->create([
            ...$request->safe()->except('image'),
            'slug' => Str::slug($request->validated('name')).'-'.Str::lower(Str::random(5)),
            'image' => $this->storeImage($request),
        ]);

        return redirect()->route('seller.products.index')->with('success', 'Produk ditambah.');
    }

    public function edit(Product $product): Response
    {
        Gate::authorize('update', $product);

        return Inertia::render('Seller/Products/Form', [
            'product' => $product,
            'categories' => Category::orderBy('sort')->get(['id', 'name']),
        ]);
    }

    public function update(SaveProductRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $product->deleteStoredImage();
            $data['image'] = $this->storeImage($request);
        }

        $product->update($data);

        return redirect()->route('seller.products.index')->with('success', 'Produk dikemas kini.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $product->deleteStoredImage();
        $product->delete();

        return back()->with('success', 'Produk dipadam.');
    }

    private function storeImage(SaveProductRequest $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return '/storage/'.$request->file('image')->store('products', 'public');
    }
}
