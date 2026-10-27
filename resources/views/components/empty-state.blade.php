@props(['icon' => 'squares', 'title' => 'Chưa có dữ liệu'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-600">
        <x-icon :name="$icon" class="h-7 w-7" />
    </div>
    <p class="mt-4 font-semibold text-gray-800">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-1 text-sm text-gray-500">{{ $slot }}</div>
    @endif
</div>
