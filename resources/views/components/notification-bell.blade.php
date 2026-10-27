{{-- Bell with the unread count and the latest notifications. "dark" sits on the green top bar, "light" in the admin header. --}}
@props(['variant' => 'dark'])

@php
    $user = auth()->user();
    $unreadCount = $user->unreadNotifications()->count();
    $latest = $user->notifications()->take(6)->get();
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" @click="open = ! open"
            @class([
                'relative rounded-full p-2 transition',
                'text-white hover:bg-white/10' => $variant === 'dark',
                'text-gray-500 hover:bg-gray-100' => $variant !== 'dark',
            ])
            aria-label="Thông báo của tôi{{ $unreadCount ? " ({$unreadCount} chưa đọc)" : '' }}">
        <x-icon name="bell" class="h-6 w-6" />
        @if ($unreadCount)
            <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[11px] font-bold leading-none text-white ring-2 {{ $variant === 'dark' ? 'ring-brand-700' : 'ring-white' }}">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-transition x-cloak
         class="fixed inset-x-2 top-16 z-50 mt-2 overflow-hidden rounded-xl bg-white text-gray-800 shadow-xl ring-1 ring-black/5 sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:w-96">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <p class="font-semibold text-gray-900">Thông báo của tôi</p>
            @if ($unreadCount)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="text-xs font-medium text-brand-700 hover:underline">Đánh dấu đã đọc hết</button>
                </form>
            @endif
        </div>

        <ul class="max-h-96 divide-y divide-gray-100 overflow-y-auto">
            @forelse ($latest as $notification)
                <li>
                    <x-notification-item :notification="$notification" />
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-gray-500">Bạn chưa có thông báo nào.</li>
            @endforelse
        </ul>

        <a href="{{ route('notifications.index') }}" class="block border-t border-gray-100 px-4 py-2.5 text-center text-sm font-medium text-brand-700 hover:bg-brand-50">Xem tất cả</a>
    </div>
</div>
