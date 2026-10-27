<x-app-layout title="Góc học tập">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Profile banner --}}
        <div class="card overflow-hidden">
            <div class="h-24 bg-gradient-to-r from-brand-700 to-teal-500"></div>
            <div class="flex flex-col gap-4 px-6 pb-6 sm:flex-row sm:items-end sm:justify-between">
                {{-- Only the avatar overlaps the banner; the greeting stays on white so it is readable --}}
                <div class="flex items-end gap-4">
                    <img src="{{ Auth::user()->avatar_url }}" alt="" class="-mt-10 h-20 w-20 shrink-0 rounded-2xl border-4 border-white object-cover shadow">
                    <div class="min-w-0 pt-3">
                        <p class="text-sm text-gray-500">Xin chào,</p>
                        <h1 class="text-xl font-bold text-gray-900">{{ Auth::user()->name }}</h1>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (Auth::user()->managementRoute())
                        <a href="{{ route(Auth::user()->managementRoute()) }}" class="btn-secondary"><x-icon name="cog" class="h-4 w-4" /> {{ Auth::user()->isAdmin() ? 'Trang quản trị' : 'Kênh giảng viên' }}</a>
                    @endif
                    <a href="{{ route('courses.index') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tìm khoá học mới</a>
                </div>
            </div>
        </div>

        {{-- Stat tiles --}}
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                ['book', 'Khoá học đã ghi danh', $stats['enrolled'], 'bg-brand-50 text-brand-700'],
                ['trophy', 'Khoá học hoàn thành', $stats['completedCourses'], 'bg-amber-50 text-amber-600'],
                ['check-circle', 'Bài học đã học', $stats['completedLessons'], 'bg-emerald-50 text-emerald-600'],
                ['chart', 'Tiến độ trung bình', $stats['averageProgress'].'%', 'bg-indigo-50 text-indigo-600'],
            ] as [$icon, $label, $value, $colors])
                <div class="card flex flex-col items-start gap-3 p-4 sm:flex-row sm:items-center sm:gap-4 sm:p-5">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $colors }}">
                        <x-icon :name="$icon" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-2xl font-bold text-gray-900">{{ $value }}</p>
                        <p class="text-xs text-gray-500 sm:text-sm">{{ $label }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- My courses --}}
            <div class="card lg:col-span-2">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h2 class="font-semibold text-gray-900">Khoá học của tôi</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($courses as $course)
                        <div class="flex flex-wrap items-center gap-4 px-4 py-4 sm:flex-nowrap sm:px-6">
                            <x-course-thumbnail :course="$course" compact class="aspect-video w-24 shrink-0 rounded-lg sm:w-36" />
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('courses.show', $course) }}" class="font-semibold text-gray-900 hover:text-brand-700">{{ $course->title }}</a>
                                <p class="text-sm text-gray-500">{{ $course->instructor->name }} · {{ $course->lessons->count() }} bài học</p>
                                <div class="mt-2 flex items-center gap-3">
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full {{ $course->progress === 100 ? 'bg-emerald-500' : 'bg-brand-600' }}" style="width: {{ $course->progress }}%"></div>
                                    </div>
                                    <span class="w-11 shrink-0 text-right text-sm font-semibold text-gray-700">{{ $course->progress }}%</span>
                                </div>
                            </div>
                            <a href="{{ route('learn.show', $course) }}" class="btn-primary w-full shrink-0 sm:w-28">
                                {{ $course->progress === 0 ? 'Bắt đầu học' : ($course->progress === 100 ? 'Ôn tập' : 'Học tiếp') }}
                            </a>
                        </div>
                    @empty
                        <x-empty-state icon="academic" title="Bạn chưa ghi danh khoá học nào">
                            <a href="{{ route('courses.index') }}" class="font-semibold text-brand-700">Khám phá khoá học ngay</a>
                        </x-empty-state>
                    @endforelse
                </div>
            </div>

            {{-- Announcements --}}
            <div class="card h-fit overflow-hidden">
                <h2 class="flex items-center gap-2 bg-brand-700 px-5 py-3 font-semibold text-white"><x-icon name="bell" class="h-5 w-5" /> Thông báo</h2>
                <ul class="divide-y divide-gray-100">
                    @forelse ($announcements as $announcement)
                        <li>
                            <a href="{{ route('announcements.show', $announcement) }}" class="block px-5 py-3 hover:bg-brand-50/50">
                                <p class="line-clamp-2 text-sm font-medium text-gray-800">{{ $announcement->title }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $announcement->published_at->format('d/m/Y') }}</p>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-gray-500">Chưa có thông báo.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
