<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every checkout of a paid course, with the revenue collected so far.
 */
class OrderController extends Controller
{
    public function __invoke(Request $request): View
    {
        $orders = Order::query()
            ->with('user')
            ->when($request->string('q')->toString(), fn (Builder $query, string $keyword) => $query->where(
                fn (Builder $query) => $query->where('code', 'like', "%{$keyword}%")
                    ->orWhere('course_title', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"))
            ))
            ->when($request->string('status')->toString(), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->string('gateway')->toString(), fn (Builder $query, string $gateway) => $query->where('gateway', $gateway))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
            'gateways' => PaymentGateway::cases(),
            'stats' => [
                'revenue' => (int) Order::paid()->sum('amount'),
                'revenueThisMonth' => (int) Order::paid()->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                'paid' => Order::paid()->count(),
                'pending' => Order::where('status', OrderStatus::Pending)->count(),
            ],
        ]);
    }
}
