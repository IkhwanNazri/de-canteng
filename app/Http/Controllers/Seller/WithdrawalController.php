<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawalController extends Controller
{
    public function index(Request $request): Response
    {
        $shop = $request->user()->shop;

        return Inertia::render('Seller/Withdrawals', [
            'balance' => $shop->walletBalance(),
            'minimum' => (float) config('marketplace.withdrawal_minimum'),
            'bank' => $shop->bank_name ? ['name' => $shop->bank_name, 'account' => $shop->bank_account] : null,
            'withdrawals' => $shop->withdrawals()->latest()->get(),
            'transactions' => $shop->walletTransactions()->latest('id')->take(20)->get(['id', 'type', 'amount', 'description', 'created_at']),
        ]);
    }

    public function store(Request $request, WithdrawalService $withdrawals): RedirectResponse
    {
        $validated = $request->validate(['amount' => ['required', 'numeric', 'min:1', 'max:1000000']], [], ['amount' => 'jumlah']);

        $withdrawals->request($request->user()->shop, (float) $validated['amount']);

        return back()->with('success', 'Permintaan pengeluaran dihantar. Admin akan proses dalam 1–3 hari bekerja.');
    }
}
