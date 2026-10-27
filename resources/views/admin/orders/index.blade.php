<x-admin-layout title="Đơn hàng">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Doanh thu', number_format($stats['revenue'], 0, ',', '.').' đ', 'bg-emerald-50 text-emerald-600', 'chart'],
            ['Doanh thu tháng này', number_format($stats['revenueThisMonth'], 0, ',', '.').' đ', 'bg-brand-50 text-brand-700', 'calendar'],
            ['Đơn đã thanh toán', $stats['paid'], 'bg-indigo-50 text-indigo-600', 'check-circle'],
            ['Đơn chờ thanh toán', $stats['pending'], 'bg-amber-50 text-amber-600', 'clock'],
        ] as [$label, $value, $colors, $icon])
            <div class="card flex items-center gap-4 p-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $colors }}"><x-icon :name="$icon" class="h-5 w-5" /></span>
                <div class="min-w-0">
                    <p class="truncate text-lg font-bold text-gray-900">{{ $value }}</p>
                    <p class="text-xs text-gray-500">{{ $label }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-6">
        <div class="border-b border-gray-100 p-4">
            <x-search-bar placeholder="Tìm theo mã đơn, học viên, email hoặc khoá học...">
                <select name="status" class="form-input mt-0 sm:w-44">
                    <option value="">Mọi trạng thái</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <select name="gateway" class="form-input mt-0 sm:w-44">
                    <option value="">Mọi cổng</option>
                    @foreach ($gateways as $gateway)
                        <option value="{{ $gateway->value }}" @selected(request('gateway') === $gateway->value)>{{ $gateway->label() }}</option>
                    @endforeach
                </select>
            </x-search-bar>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Mã đơn</th><th>Học viên</th><th>Khoá học</th><th class="text-right">Số tiền</th><th>Trạng thái</th><th>Thời gian</th></tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="whitespace-nowrap font-mono text-xs">
                                {{ $order->code }}
                                @if ($order->transaction_no)
                                    <p class="text-gray-400">GD: {{ $order->transaction_no }}</p>
                                @endif
                            </td>
                            <td>
                                <p class="font-medium text-gray-900">{{ $order->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $order->user->email }}</p>
                            </td>
                            <td class="max-w-xs truncate">{{ $order->course_title }}</td>
                            <td class="whitespace-nowrap text-right">
                                <p class="font-semibold">{{ $order->amount_label }}</p>
                                <p class="text-xs text-gray-500">{{ $order->gateway->label() }}</p>
                            </td>
                            <td>
                                <span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
                                @if ($order->failure_reason)
                                    <p class="mt-1 max-w-[12rem] text-xs text-gray-500">{{ $order->failure_reason }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-xs text-gray-500">{{ ($order->paid_at ?? $order->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="document" title="Chưa có đơn hàng nào" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $orders->links() }}</div>
    </div>
</x-admin-layout>
