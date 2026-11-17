<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawalController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(WithdrawalStatus::class)]])['status'] ?? 'pending';

        return Inertia::render('Admin/Withdrawals', [
            'withdrawals' => Withdrawal::with('shop:id,name,slug')
                ->where('status', $status)
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'pendingTotal' => (float) Withdrawal::where('status', WithdrawalStatus::Pending)->sum('amount'),
        ]);
    }

    public function pay(Request $request, Withdrawal $withdrawal, WithdrawalService $withdrawals): RedirectResponse
    {
        $validated = $request->validate(['reference' => ['required', 'string', 'max:100']], [], ['reference' => 'rujukan pindahan']);

        $withdrawals->markPaid($withdrawal, $request->user(), $validated['reference']);

        return back()->with('success', 'Pengeluaran ditanda sebagai dibayar.');
    }

    public function reject(Request $request, Withdrawal $withdrawal, WithdrawalService $withdrawals): RedirectResponse
    {
        $validated = $request->validate(['admin_note' => ['required', 'string', 'max:255']], [], ['admin_note' => 'sebab']);

        $withdrawals->reject($withdrawal, $request->user(), $validated['admin_note']);

        return back()->with('success', 'Pengeluaran ditolak dan baki dikembalikan kepada penjual.');
    }
}
