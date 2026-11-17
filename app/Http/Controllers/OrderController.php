<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Orders/Index', [
            'orders' => $request->user()->orders()
                ->with('shop:id,name,slug', 'items:id,order_id,product_name,quantity,line_total')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return Inertia::render('Orders/Show', [
            'order' => $order->load('shop:id,name,slug', 'items'),
        ]);
    }
}
