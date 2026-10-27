<x-admin-layout title="Bảng điều khiển giảng viên">
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['book', 'Khoá học của tôi', number_format($stats['courses']), 'from-brand-500 to-brand-700'],
            ['users', 'Học viên', number_format($stats['students']), 'from-sky-500 to-indigo-600'],
            ['star', 'Điểm đánh giá TB', $stats['averageRating'] ? number_format($stats['averageRating'], 1).' / 5' : '—', 'from-rose-400 to-pink-600'],
            ['clock', 'Đang chờ duyệt', number_format($stats['pending']), 'from-amber-400 to-orange-500'],
        ] as [$icon, $label, $value, $gradient])
            <div class="card relative overflow-hidden p-5">
                <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-gradient-to-br {{ $gradient }} opacity-10"></div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br {{ $gradient }} text-white shadow">
                    <x-icon :name="$icon" class="h-5 w-5" />
                </span>
                <p class="mt-4 text-3xl font-bold text-gray-900">{{ $value }}</p>
                <p class="text-sm text-gray-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card p-5 xl:col-span-2">
            <h2 class="font-semibold text-gray-900">Lượt ghi danh vào khoá của tôi (6 tháng)</h2>
            <div class="mt-4 h-72">
                <canvas data-chart="bar" data-label="Lượt ghi danh"
                        data-labels='@json($enrollmentChart['labels'])'
                        data-values='@json($enrollmentChart['values'])'></canvas>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Ghi danh mới nhất</h2>
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
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Khoá học của tôi</h2>
            <a href="{{ route('instructor.courses.index') }}" class="text-sm font-medium text-brand-700">Quản lý</a>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Khoá học</th><th>Trạng thái</th><th class="text-center">Học viên</th><th class="text-center">Đánh giá TB</th><th class="text-right">Thao tác</th></tr>
                </thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr>
                            <td class="max-w-xs truncate font-medium text-gray-900">{{ $course->title }}</td>
                            <td><span class="badge {{ $course->status->badgeClasses() }}">{{ $course->status->label() }}</span></td>
                            <td class="text-center">{{ $course->enrollments_count }}</td>
                            <td class="text-center">{{ $course->reviews_avg_rating ? number_format($course->reviews_avg_rating, 1) : '—' }}</td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('instructor.courses.students', $course) }}" class="btn-ghost btn-sm" title="Học viên"><x-icon name="users" class="h-4 w-4" /></a>
                                <a href="{{ route('instructor.courses.curriculum', $course) }}" class="btn-ghost btn-sm" title="Chương & bài học"><x-icon name="list" class="h-4 w-4" /></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="book" title="Bạn chưa có khoá học nào" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
