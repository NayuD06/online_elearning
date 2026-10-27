<x-app-layout :title="'Giảng viên '.$instructor->name">
    <section class="bg-gradient-to-br from-brand-800 to-brand-600 text-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center gap-6 px-4 py-10 text-center sm:flex-row sm:text-left sm:px-6 lg:px-8">
            <img src="{{ $instructor->avatar_url }}" alt="" class="h-28 w-28 shrink-0 rounded-full border-4 border-white/60 object-cover shadow-lg">
            <div class="min-w-0">
                <p class="text-sm font-medium uppercase tracking-wide text-brand-100">Giảng viên</p>
                <h1 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $instructor->name }}</h1>
                <div class="mt-3 flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm sm:justify-start">
                    <span class="flex items-center gap-1"><x-icon name="book" class="h-4 w-4" /> {{ $stats['courses'] }} khoá học</span>
                    <span class="flex items-center gap-1"><x-icon name="users" class="h-4 w-4" /> {{ $stats['students'] }} học viên</span>
                    <span class="flex items-center gap-1"><x-icon name="star" class="h-4 w-4" /> {{ number_format($stats['rating'], 1) }} ({{ $stats['reviews'] }} đánh giá)</span>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-3 lg:px-8">
        <aside class="card h-fit p-6">
            <h2 class="font-semibold text-gray-900">Giới thiệu</h2>
            @if ($instructor->bio)
                <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ $instructor->bio }}</p>
            @else
                <p class="mt-3 text-sm text-gray-500">Giảng viên chưa cập nhật phần giới thiệu.</p>
            @endif
        </aside>

        <div class="lg:col-span-2">
            <h2 class="text-xl font-bold text-gray-900">Khoá học của giảng viên</h2>
            @if ($courses->isEmpty())
                <div class="card mt-4">
                    <x-empty-state icon="book" title="Giảng viên chưa có khoá học công khai" />
                </div>
            @else
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    @foreach ($courses as $course)
                        <x-course-card :course="$course" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
