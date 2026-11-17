<?php

namespace App\Http\Controllers\Seller;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(OrderStatus::class)]])['status'] ?? null;

        return Inertia::render('Seller/Orders/Index', [
            'orders' => $request->user()->shop->orders()
                ->with('buyer:id,name', 'items:id,order_id,product_name,quantity')
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'status' => $status,
            'statuses' => collect(OrderStatus::cases())->map(fn (OrderStatus $case) => ['value' => $case->value, 'label' => $case->label()]),
        ]);
    }

    public function ship(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->shop_id === $request->user()->shop?->id, 403);
        abort_unless($order->status === OrderStatus::Paid, 422, 'Hanya pesanan yang sudah dibayar boleh dihantar.');

        $validated = $request->validate(['tracking_number' => ['required', 'string', 'max:50']]);

        $order->update([
            'status' => OrderStatus::Shipped,
            'tracking_number' => $validated['tracking_number'],
            'shipped_at' => now(),
        ]);

        return back()->with('success', "Pesanan {$order->number} ditanda sebagai dihantar.");
    }
}
