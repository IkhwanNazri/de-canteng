<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class SalesReport
{
    /**
     * Statuses that count as a completed sale.
     *
     * @return list<OrderStatus>
     */
    public static function soldStatuses(): array
    {
        return [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Completed];
    }

    /**
     * @return Builder<Order>
     */
    public function soldOrders(?int $shopId = null): Builder
    {
        return Order::query()
            ->whereIn('status', self::soldStatuses())
            ->when($shopId, fn (Builder $query) => $query->where('shop_id', $shopId));
    }

    /**
     * Daily sales totals for the last N days, oldest first, including empty days.
     *
     * @return list<array{date: string, total: float}>
     */
    public function dailySales(int $days = 14, ?int $shopId = null): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $totals = $this->soldOrders($shopId)
            ->where('paid_at', '>=', $from)
            ->selectRaw('DATE(paid_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, $days - 1))->map(function (int $offset) use ($from, $totals) {
            $day = $from->copy()->addDays($offset)->toDateString();

            return ['date' => $day, 'total' => (float) ($totals[$day] ?? 0)];
        })->all();
    }
}
