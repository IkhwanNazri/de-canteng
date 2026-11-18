<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __invoke(Request $request, ReferralService $referrals, VoucherService $vouchers): Response
    {
        $user = $request->user();
        $code = $referrals->codeFor($user);

        return Inertia::render('Dashboard', [
            'loyalty' => [
                'stamps' => $user->stamps,
                'perCard' => (int) config('marketplace.stamps_per_card'),
                'reward' => (float) config('marketplace.stamp_reward'),
                'minOrder' => (float) config('marketplace.stamp_min_order'),
                'history' => $user->stampEntries()->latest('id')->take(8)->get(['id', 'stamps', 'reason', 'created_at']),
            ],
            'referral' => [
                'code' => $code,
                'link' => route('register', ['ref' => $code]),
                'friends' => $user->referrals()->count(),
                'referrerStamps' => (int) config('marketplace.referral_stamps_referrer'),
                'refereeStamps' => (int) config('marketplace.referral_stamps_referee'),
                'freeOrders' => (int) config('marketplace.referral_commission_free_orders'),
            ],
            'voucherCount' => $vouchers->usable($user)->count(),
            'commissionFreeOrders' => $user->shop?->commission_free_orders,
        ]);
    }
}
