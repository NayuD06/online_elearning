@props(['placeholder' => 'Tìm kiếm...', 'action' => url()->current()])

<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'flex flex-col gap-2 sm:flex-row sm:items-center']) }}>
    <div class="relative flex-1">
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="form-input mt-0 pl-9">
    </div>
    {{ $slot }}
    <button class="btn-secondary">Lọc</button>
</form>
