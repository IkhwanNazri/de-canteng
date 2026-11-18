<?php

namespace App\Http\Controllers\Seller;

use App\Enums\DiscountType;
use App\Enums\VoucherScope;
use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Seller/Vouchers/Index', [
            'vouchers' => $request->user()->shop->vouchers()->withCount('products')->latest()->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Seller/Vouchers/Create', [
            'products' => $request->user()->shop->products()->orderBy('name')->get(['id', 'name', 'price']),
            'scopes' => collect([VoucherScope::Shop, VoucherScope::Product, VoucherScope::Follower, VoucherScope::Live])
                ->map(fn (VoucherScope $scope) => ['value' => $scope->value, 'label' => $scope->label()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shop = $request->user()->shop;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'code' => ['nullable', 'alpha_num:ascii', 'min:4', 'max:15', Rule::unique('vouchers', 'code')],
            'scope' => ['required', Rule::in([VoucherScope::Shop->value, VoucherScope::Product->value, VoucherScope::Follower->value, VoucherScope::Live->value])],
            'discount_type' => ['required', Rule::enum(DiscountType::class)],
            'discount_value' => ['required', 'numeric', 'min:1', $request->input('discount_type') === 'percent' ? 'max:90' : 'max:10000'],
            'max_discount' => ['nullable', 'numeric', 'min:1', 'max:10000'],
            'min_spend' => ['required', 'numeric', 'min:0', 'max:100000'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['required', 'date', 'after:now', 'after:starts_at'],
            'product_ids' => [Rule::requiredIf($request->input('scope') === VoucherScope::Product->value), 'array'],
            'product_ids.*' => ['integer', Rule::exists('products', 'id')->where('shop_id', $shop->id)],
        ], [], [
            'name' => 'nama baucar', 'code' => 'kod', 'discount_value' => 'nilai diskaun', 'max_discount' => 'diskaun maksimum',
            'min_spend' => 'belanja minimum', 'quantity' => 'kuantiti', 'ends_at' => 'tarikh tamat', 'product_ids' => 'produk',
        ]);

        $voucher = $shop->vouchers()->create([
            ...collect($validated)->except('product_ids')->all(),
            'code' => Str::upper($validated['code'] ?? Str::random(8)),
            'max_discount' => $validated['discount_type'] === 'percent' ? ($validated['max_discount'] ?? null) : null,
        ]);

        if ($voucher->scope === VoucherScope::Product) {
            $voucher->products()->sync($validated['product_ids']);
        }

        return redirect()->route('seller.vouchers.index')->with('success', "Baucar {$voucher->code} dicipta.");
    }

    public function toggle(Request $request, Voucher $voucher): RedirectResponse
    {
        abort_unless($voucher->shop_id === $request->user()->shop?->id, 403);

        $voucher->update(['is_active' => ! $voucher->is_active]);

        return back();
    }
}
