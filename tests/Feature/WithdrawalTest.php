<?php

namespace Tests\Feature;

use App\Enums\WalletEntryType;
use App\Enums\WithdrawalStatus;
use App\Models\Shop;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalProcessedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private function shopWithBalance(float $balance): Shop
    {
        $shop = Shop::factory()->create(['bank_name' => 'Maybank', 'bank_account' => '164000000000']);
        WalletTransaction::create(['shop_id' => $shop->id, 'type' => WalletEntryType::SaleCredit, 'amount' => $balance, 'description' => 'Jualan']);

        return $shop;
    }

    public function test_seller_request_holds_the_amount_from_the_balance(): void
    {
        $shop = $this->shopWithBalance(200);

        $this->actingAs($shop->owner)->post(route('seller.withdrawals.store'), ['amount' => 150])->assertSessionHasNoErrors();

        $withdrawal = $shop->withdrawals()->sole();
        $this->assertSame(WithdrawalStatus::Pending, $withdrawal->status);
        $this->assertSame('164000000000', $withdrawal->bank_account);
        $this->assertEquals(50, $shop->walletBalance());
    }

    public function test_request_is_refused_above_balance_below_minimum_or_while_one_is_pending(): void
    {
        $shop = $this->shopWithBalance(100);

        $this->actingAs($shop->owner)->post(route('seller.withdrawals.store'), ['amount' => 150])->assertSessionHasErrors('amount');
        $this->actingAs($shop->owner)->post(route('seller.withdrawals.store'), ['amount' => 5])->assertSessionHasErrors('amount');

        $this->actingAs($shop->owner)->post(route('seller.withdrawals.store'), ['amount' => 20]);
        $this->actingAs($shop->owner)->post(route('seller.withdrawals.store'), ['amount' => 20])->assertSessionHasErrors('amount');

        $this->assertSame(1, $shop->withdrawals()->count());
    }

    public function test_request_requires_bank_details(): void
    {
        $shop = $this->shopWithBalance(100);
        $shop->update(['bank_name' => null, 'bank_account' => null]);

        $this->actingAs($shop->owner)->post(route('seller.withdrawals.store'), ['amount' => 50])->assertSessionHasErrors('amount');
    }

    public function test_admin_marks_a_withdrawal_paid_and_the_seller_is_emailed(): void
    {
        Notification::fake();
        $shop = $this->shopWithBalance(100);
        $this->actingAs($shop->owner)->post(route('seller.withdrawals.store'), ['amount' => 100]);
        $withdrawal = $shop->withdrawals()->sole();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.withdrawals.pay', $withdrawal), ['reference' => 'M2U-998877']);
        $this->actingAs($admin)->post(route('admin.withdrawals.reject', $withdrawal), ['admin_note' => 'x'])->assertSessionHasErrors('withdrawal');

        $withdrawal->refresh();
        $this->assertSame(WithdrawalStatus::Paid, $withdrawal->status);
        $this->assertSame($admin->id, $withdrawal->processed_by);
        $this->assertEquals(0, $shop->walletBalance());
        Notification::assertSentTo($shop->owner, WithdrawalProcessedNotification::class);
    }

    public function test_rejecting_returns_the_held_amount(): void
    {
        Notification::fake();
        $shop = $this->shopWithBalance(80);
        $this->actingAs($shop->owner)->post(route('seller.withdrawals.store'), ['amount' => 80]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.withdrawals.reject', $shop->withdrawals()->sole()), ['admin_note' => 'Nombor akaun salah']);

        $this->assertSame(WithdrawalStatus::Rejected, $shop->withdrawals()->sole()->status);
        $this->assertEquals(80, $shop->walletBalance());
    }

    public function test_only_admins_can_process_withdrawals(): void
    {
        $withdrawal = Withdrawal::factory()->create();

        $this->actingAs($withdrawal->shop->owner)->post(route('admin.withdrawals.pay', $withdrawal), ['reference' => 'x'])->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.withdrawals.index'))->assertForbidden();
    }
}
