<x-admin-layout title="Bảng điều khiển">
    @if ($stats['pending'])
        <div class="card mb-6 overflow-hidden border border-amber-200">
            <div class="flex items-center justify-between bg-amber-50 px-5 py-3">
                <h2 class="font-semibold text-amber-800">{{ $stats['pending'] }} khoá học đang chờ duyệt</h2>
                <a href="{{ route('admin.courses.index', ['status' => 'pending']) }}" class="text-sm font-medium text-amber-800 underline">Duyệt ngay</a>
            </div>
            <ul class="divide-y divide-gray-100">
                @foreach ($pendingCourses as $course)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <a href="{{ route('courses.show', $course) }}" target="_blank" class="min-w-0 flex-1 truncate text-sm font-medium text-gray-900 hover:text-brand-700">{{ $course->title }}</a>
                        <span class="shrink-0 text-xs text-gray-500">{{ $course->instructor->name }} · {{ $course->submitted_at?->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['book', 'Khoá học', $stats['courses'], 'from-brand-500 to-brand-700'],
            ['users', 'Học viên', $stats['students'], 'from-sky-500 to-indigo-600'],
            ['clipboard', 'Lượt ghi danh', $stats['enrollments'], 'from-amber-400 to-orange-500'],
            ['star', 'Đánh giá', $stats['reviews'], 'from-rose-400 to-pink-600'],
        ] as [$icon, $label, $value, $gradient])
            <div class="card relative overflow-hidden p-5">
                <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-gradient-to-br {{ $gradient }} opacity-10"></div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br {{ $gradient }} text-white shadow">
                    <x-icon :name="$icon" class="h-5 w-5" />
                </span>
                <p class="mt-4 text-3xl font-bold text-gray-900">{{ number_format($value) }}</p>
                <p class="text-sm text-gray-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card p-5 xl:col-span-2">
            <h2 class="font-semibold text-gray-900">Lượt ghi danh 6 tháng gần nhất</h2>
            <div class="mt-4 h-72">
                <canvas data-chart="bar" data-label="Lượt ghi danh"
                        data-labels='@json($enrollmentChart['labels'])'
                        data-values='@json($enrollmentChart['values'])'></canvas>
            </div>
        </div>
        <div class="card p-5">
            <h2 class="font-semibold text-gray-900">Khoá học theo danh mục</h2>
            <div class="mt-4 h-72">
                <canvas data-chart="doughnut" data-label="Khoá học"
                        data-labels='@json($categoryChart->pluck('name'))'
                        data-values='@json($categoryChart->pluck('courses_count'))'></canvas>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Ghi danh mới nhất</h2>
                <a href="{{ route('admin.enrollments.index') }}" class="text-sm font-medium text-brand-700">Xem tất cả</a>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($latestEnrollments as $enrollment)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <img src="{{ $enrollment->user->avatar_url }}" alt="" class="h-9 w-9 rounded-full object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900">{{ $enrollment->user->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $enrollment->course->title }}</p>
                        </div>
                        <span class="shrink-0 text-xs text-gray-400">{{ $enrollment->created_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li><x-empty-state icon="clipboard" title="Chưa có lượt ghi danh" /></li>
                @endforelse
            </ul>
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Khoá học nổi bật</h2>
                <a href="{{ route('admin.courses.index') }}" class="text-sm font-medium text-brand-700">Quản lý</a>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($popularCourses as $course)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-sm font-bold text-brand-700">{{ $loop->iteration }}</span>
                        <a href="{{ route('courses.show', $course) }}" target="_blank" class="min-w-0 flex-1 truncate text-sm font-medium text-gray-900 hover:text-brand-700">{{ $course->title }}</a>
                        <span class="badge bg-gray-100 text-gray-700">{{ $course->enrollments_count }} học viên</span>
                    </li>
                @empty
                    <li><x-empty-state icon="book" title="Chưa có khoá học" /></li>
                @endforelse
            </ul>
        </div>
    </div>
</x-admin-layout>
