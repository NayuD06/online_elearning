<x-app-layout :title="'Đơn hàng '.$order->code">
    <div class="mx-auto max-w-xl px-4 py-12 sm:px-6">
        <div class="card p-6 text-center sm:p-8">
            @if ($order->isPaid())
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><x-icon name="check-circle" class="h-9 w-9" /></span>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">Thanh toán thành công</h1>
                <p class="mt-1 text-gray-600">Bạn đã được ghi danh vào khoá học. Chúc bạn học tốt!</p>
            @elseif ($order->isPending())
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600"><x-icon name="clock" class="h-9 w-9" /></span>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">Đang chờ thanh toán</h1>
                <p class="mt-1 text-gray-600">Chưa nhận được kết quả từ cổng thanh toán. Hãy tải lại trang sau ít phút.</p>
            @else
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-rose-600"><x-icon name="x" class="h-9 w-9" /></span>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">Thanh toán không thành công</h1>
                <p class="mt-1 text-gray-600">{{ $order->failure_reason }}</p>
            @endif

            <dl class="mt-6 space-y-2 rounded-xl bg-gray-50 p-4 text-left text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Mã đơn hàng</dt><dd class="font-mono">{{ $order->code }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Khoá học</dt><dd class="text-right font-medium">{{ $order->course_title }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Số tiền</dt><dd class="font-semibold">{{ $order->amount_label }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Cổng thanh toán</dt><dd>{{ $order->gateway->label() }}</dd></div>
                @if ($order->transaction_no)
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Mã giao dịch</dt><dd class="font-mono">{{ $order->transaction_no }}</dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Thời gian</dt><dd>{{ ($order->paid_at ?? $order->created_at)->format('d/m/Y H:i') }}</dd></div>
            </dl>

            <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
                @if ($order->isPaid() && $order->course)
                    <a href="{{ route('learn.show', $order->course) }}" class="btn-primary"><x-icon name="play" class="h-5 w-5" /> Vào học ngay</a>
                @elseif ($order->course)
                    <a href="{{ route('payments.checkout', $order->course) }}" class="btn-primary">Thử thanh toán lại</a>
                @endif
                <a href="{{ route('orders.index') }}" class="btn-secondary">Lịch sử thanh toán</a>
            </div>
        </div>
    </div>
</x-app-layout>
