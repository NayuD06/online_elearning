@props(['notification'])

@php($data = $notification->data)

<a href="{{ route('notifications.open', $notification->id) }}"
   @class(['flex gap-3 px-4 py-3 transition hover:bg-gray-50', 'bg-brand-50/60' => ! $notification->read_at])>
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700">
        <x-icon :name="$data['icon'] ?? 'bell'" class="h-5 w-5" />
    </span>
    <span class="min-w-0 flex-1">
        <span class="block text-sm font-semibold text-gray-900">{{ $data['title'] ?? 'Thông báo' }}</span>
        @if (! empty($data['body']))
            <span class="mt-0.5 block text-sm text-gray-600">{{ $data['body'] }}</span>
        @endif
        <span class="mt-1 block text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
    </span>
    @unless ($notification->read_at)
        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-brand-600" aria-label="Chưa đọc"></span>
    @endunless
</a>
