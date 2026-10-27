@php
    $messages = array_filter([
        'success' => session('success'),
        'error' => session('error'),
    ]);
@endphp

@foreach ($messages as $type => $message)
    <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
         @class([
             'mb-4 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm',
             'border-emerald-200 bg-emerald-50 text-emerald-800' => $type === 'success',
             'border-rose-200 bg-rose-50 text-rose-800' => $type === 'error',
         ]) role="alert">
        <x-icon :name="$type === 'success' ? 'check-circle' : 'bell'" class="mt-0.5 h-5 w-5 shrink-0" />
        <p class="flex-1">{{ $message }}</p>
        <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Đóng">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>
@endforeach
