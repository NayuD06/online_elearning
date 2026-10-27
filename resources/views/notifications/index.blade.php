<x-app-layout title="Thông báo của tôi">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900">Thông báo của tôi</h1>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="btn-secondary"><x-icon name="check" class="h-4 w-4" /> Đánh dấu đã đọc hết</button>
                </form>
            @endif
        </div>

        <div class="card mt-6 overflow-hidden">
            <ul class="divide-y divide-gray-100">
                @forelse ($notifications as $notification)
                    <li><x-notification-item :notification="$notification" /></li>
                @empty
                    <li><x-empty-state icon="bell" title="Bạn chưa có thông báo nào">Khi khoá học được duyệt, câu hỏi có trả lời hay bạn nhận chứng chỉ, thông báo sẽ hiện ở đây.</x-empty-state></li>
                @endforelse
            </ul>
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    </div>
</x-app-layout>
