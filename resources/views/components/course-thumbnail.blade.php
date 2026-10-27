@props(['course', 'compact' => false])

@php
    $gradients = ['from-teal-500 to-cyan-600', 'from-emerald-500 to-teal-700', 'from-sky-500 to-indigo-600', 'from-amber-400 to-orange-500', 'from-fuchsia-500 to-purple-600', 'from-rose-400 to-pink-600'];
    $gradient = $gradients[$course->id % count($gradients)];
@endphp

@if ($course->thumbnail_url)
    <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" loading="lazy" {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => "flex items-center justify-center bg-gradient-to-br {$gradient}"]) }}>
        @if ($compact)
            {{-- Small thumbnails (tables, lists) only have room for the icon --}}
            <x-icon name="academic" class="h-6 w-6 text-white opacity-90" />
        @else
            <div class="px-4 text-center text-white">
                <x-icon name="academic" class="mx-auto h-10 w-10 opacity-80" />
                <p class="mt-2 line-clamp-2 text-sm font-semibold opacity-95">{{ $course->category?->name ?? 'E-Learning' }}</p>
            </div>
        @endif
    </div>
@endif
