<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Services\SalesReport;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(SalesReport $report): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'gmv' => (float) $report->soldOrders()->sum('total'),
                'commission' => (float) WalletTransaction::whereNull('shop_id')->sum('amount'),
                'orders' => $report->soldOrders()->count(),
                'buyers' => User::where('role', UserRole::Buyer)->count(),
                'shops' => Shop::count(),
                'pendingWithdrawals' => Withdrawal::where('status', 'pending')->count(),
            ],
            'dailySales' => $report->dailySales(14),
            'recentOrders' => Order::with('shop:id,name', 'buyer:id,name')->latest()->take(8)->get(),
            'topShops' => Shop::query()
                ->withSum(['orders as sales' => fn ($query) => $query->whereIn('status', SalesReport::soldStatuses())], 'total')
                ->withCount(['orders as orders_count' => fn ($query) => $query->whereIn('status', SalesReport::soldStatuses())])
                ->orderByDesc('sales')
                ->take(5)
                ->get(['id', 'name', 'slug']),
        ]);
    }
}
