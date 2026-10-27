<x-app-layout title="Lịch sử thanh toán">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900">Lịch sử thanh toán</h1>

        <div class="card mt-6 overflow-hidden">
            <ul class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <li>
                        <a href="{{ route('payments.show', $order) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-4 hover:bg-gray-50">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900">{{ $order->course_title }}</p>
                                <p class="text-xs text-gray-500"><span class="font-mono">{{ $order->code }}</span> · {{ $order->gateway->label() }} · {{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <span class="font-semibold text-gray-900">{{ $order->amount_label }}</span>
                            <span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
                        </a>
                    </li>
                @empty
                    <li><x-empty-state icon="document" title="Bạn chưa mua khoá học nào">
                        <a href="{{ route('courses.index', ['price' => 'paid']) }}" class="font-semibold text-brand-700">Xem các khoá học trả phí</a>
                    </x-empty-state></li>
                @endforelse
            </ul>
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    </div>
</x-app-layout>
