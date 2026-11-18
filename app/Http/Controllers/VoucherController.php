<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function index(Request $request, VoucherService $vouchers): Response
    {
        $present = fn ($owned) => [
            'id' => $owned->id,
            'used_at' => $owned->used_at,
            'voucher' => $owned->voucher->only('id', 'code', 'name', 'label', 'scope', 'scope_label', 'min_spend', 'max_discount', 'ends_at'),
            'shop' => $owned->voucher->shop?->only('name', 'slug'),
        ];

        return Inertia::render('Vouchers/Index', [
            'usable' => $vouchers->usable($request->user())->load('voucher.shop:id,name,slug')->map($present)->values(),
            'used' => $request->user()->vouchers()->whereNotNull('used_at')->with('voucher.shop:id,name,slug')->latest('used_at')->take(20)->get()->map($present),
        ]);
    }

    public function claim(Request $request, Voucher $voucher, VoucherService $vouchers): RedirectResponse
    {
        $vouchers->claim($request->user(), $voucher);

        return back()->with('success', "Baucar {$voucher->code} disimpan. Guna semasa checkout.");
    }
}
