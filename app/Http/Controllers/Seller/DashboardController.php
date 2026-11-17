<?php

namespace App\Http\Controllers\Seller;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Services\SalesReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SalesReport $report): Response
    {
        $shop = $request->user()->shop;

        return Inertia::render('Seller/Dashboard', [
            'shop' => $shop->only('id', 'name', 'slug'),
            'stats' => [
                'balance' => $shop->walletBalance(),
                'salesThisMonth' => (float) $report->soldOrders($shop->id)->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
                'ordersToShip' => $shop->orders()->where('status', OrderStatus::Paid)->count(),
                'products' => $shop->products()->count(),
                'commissionPaid' => (float) -$shop->walletTransactions()->where('type', 'commission_debit')->sum('amount'),
            ],
            'dailySales' => $report->dailySales(14, $shop->id),
            'recentOrders' => $shop->orders()->with('buyer:id,name')->latest()->take(5)->get(),
        ]);
    }
}
