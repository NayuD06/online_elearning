@props(['course'])

<a href="{{ route('courses.show', $course) }}" class="card group flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-lg">
    <div class="relative">
        <x-course-thumbnail :course="$course" class="aspect-[16/9] w-full" />
        <span class="badge absolute left-3 top-3 bg-white/95 text-brand-800 shadow">{{ $course->level->label() }}</span>
    </div>

    <div class="flex flex-1 flex-col p-4">
        @if ($course->category)
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">{{ $course->category->name }}</p>
        @endif

        {{-- min-h keeps rating rows aligned across a grid row when some titles wrap to two lines --}}
        <h3 class="mt-1 line-clamp-2 min-h-[3rem] font-semibold text-gray-900 group-hover:text-brand-700">{{ $course->title }}</h3>
        <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $course->summary }}</p>

        <div class="mt-3 flex items-center gap-2 text-sm">
            <span class="font-semibold text-amber-500">{{ number_format((float) ($course->reviews_avg_rating ?? 0), 1) }}</span>
            <x-stars :rating="$course->reviews_avg_rating ?? 0" />
            <span class="text-gray-400">({{ $course->enrollments_count ?? 0 }} học viên)</span>
        </div>

        <div class="mt-auto flex items-center justify-between border-t border-gray-100 pt-3 text-sm">
            <span class="flex items-center gap-1 text-gray-500">
                <x-icon name="book" class="h-4 w-4" /> {{ $course->lessons_count ?? 0 }} bài học
            </span>
            <span @class(['font-bold', 'text-emerald-600' => $course->isFree(), 'text-brand-800' => ! $course->isFree()])>{{ $course->price_label }}</span>
        </div>
    </div>
</a>
