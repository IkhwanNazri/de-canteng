<?php

namespace App\Services;

use App\Enums\WalletEntryType;
use App\Enums\WithdrawalStatus;
use App\Models\Shop;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalProcessedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawalService
{
    /**
     * Hold the amount from the seller's balance until an admin pays or rejects it.
     */
    public function request(Shop $shop, float $amount): Withdrawal
    {
        return DB::transaction(function () use ($shop, $amount) {
            $shop = Shop::whereKey($shop->id)->lockForUpdate()->first();
            $minimum = (float) config('marketplace.withdrawal_minimum');

            $error = match (true) {
                ! $shop->bank_name || ! $shop->bank_account => 'Sila isi maklumat bank dalam Tetapan Kedai dahulu.',
                $shop->withdrawals()->where('status', WithdrawalStatus::Pending)->exists() => 'Anda masih ada permintaan yang belum diproses.',
                $amount < $minimum => 'Jumlah minimum pengeluaran ialah RM'.number_format($minimum, 2).'.',
                round($amount, 2) > round($shop->walletBalance(), 2) => 'Jumlah melebihi baki anda.',
                default => null,
            };

            if ($error) {
                throw ValidationException::withMessages(['amount' => $error]);
            }

            $withdrawal = $shop->withdrawals()->create([
                'amount' => $amount,
                'status' => WithdrawalStatus::Pending,
                'bank_name' => $shop->bank_name,
                'bank_account' => $shop->bank_account,
            ]);

            $this->record($withdrawal, WalletEntryType::WithdrawalHold, -$amount, "Pengeluaran #{$withdrawal->id}");

            return $withdrawal;
        });
    }

    public function markPaid(Withdrawal $withdrawal, User $admin, string $reference): void
    {
        $this->process($withdrawal, $admin, function (Withdrawal $withdrawal) use ($reference) {
            $withdrawal->update(['status' => WithdrawalStatus::Paid, 'reference' => $reference]);
        });
    }

    public function reject(Withdrawal $withdrawal, User $admin, string $note): void
    {
        $this->process($withdrawal, $admin, function (Withdrawal $withdrawal) use ($note) {
            $withdrawal->update(['status' => WithdrawalStatus::Rejected, 'admin_note' => $note]);
            $this->record($withdrawal, WalletEntryType::WithdrawalReversal, $withdrawal->amount, "Pengeluaran #{$withdrawal->id} ditolak");
        });
    }

    /**
     * @param  callable(Withdrawal): void  $apply
     */
    private function process(Withdrawal $withdrawal, User $admin, callable $apply): void
    {
        DB::transaction(function () use ($withdrawal, $admin, $apply) {
            $locked = Withdrawal::whereKey($withdrawal->id)->lockForUpdate()->first();

            if ($locked->status !== WithdrawalStatus::Pending) {
                throw ValidationException::withMessages(['withdrawal' => 'Permintaan ini sudah diproses.']);
            }

            $locked->forceFill(['processed_by' => $admin->id, 'processed_at' => now()]);
            $apply($locked);
        });

        $withdrawal->refresh()->shop->owner->notify(new WithdrawalProcessedNotification($withdrawal));
    }

    private function record(Withdrawal $withdrawal, WalletEntryType $type, float $amount, string $description): void
    {
        WalletTransaction::create([
            'shop_id' => $withdrawal->shop_id,
            'withdrawal_id' => $withdrawal->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
        ]);
    }
}
