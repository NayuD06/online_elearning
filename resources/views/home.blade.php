<x-app-layout title="Học trực tuyến mọi lúc, mọi nơi">
    {{-- Banner slides managed in /admin/banners --}}
    @if ($banners->isNotEmpty())
        <section aria-roledescription="carousel" aria-label="Banner" class="relative overflow-hidden bg-gray-900"
                 x-data="{
                     current: 0,
                     total: {{ $banners->count() }},
                     paused: false,
                     touchX: null,
                     go(index) { this.current = (index + this.total) % this.total },
                     init() {
                         if (this.total < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                         setInterval(() => { if (! this.paused && ! document.hidden) this.go(this.current + 1) }, 6000);
                     },
                 }"
                 @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false"
                 @touchstart.passive="touchX = $event.touches[0].clientX"
                 @touchend="if (touchX !== null && Math.abs($event.changedTouches[0].clientX - touchX) > 40) go(current + ($event.changedTouches[0].clientX < touchX ? 1 : -1)); touchX = null"
                 @keydown.left="go(current - 1)" @keydown.right="go(current + 1)">
            <div class="relative h-64 sm:h-80 lg:h-[26rem]">
                @foreach ($banners as $banner)
                    <div x-show="current === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                         x-transition:enter="transition-opacity duration-700" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="absolute inset-0 transition-opacity duration-700" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}"
                         class="absolute inset-0">
                        <img src="{{ $banner->image_url }}" alt="" class="h-full w-full object-cover" @if (! $loop->first) loading="lazy" @endif>
                        <div class="absolute inset-0 bg-gradient-to-r from-gray-900/80 via-gray-900/40 to-transparent"></div>
                        <div class="absolute inset-0 mx-auto flex max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                            <div class="max-w-xl text-white">
                                <h2 class="text-2xl font-bold leading-tight sm:text-4xl">{{ $banner->title }}</h2>
                                @if ($banner->subtitle)
                                    <p class="mt-3 line-clamp-3 text-sm text-gray-100 sm:text-lg">{{ $banner->subtitle }}</p>
                                @endif
                                @if ($banner->link_url)
                                    <a href="{{ $banner->link_url }}" @if ($banner->is_external_link) target="_blank" rel="noopener" @endif
                                       class="btn mt-6 bg-amber-400 text-gray-900 hover:bg-amber-300">
                                        {{ $banner->button_label ?: 'Xem ngay' }} <x-icon name="arrow-right" class="h-4 w-4" />
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($banners->count() > 1)
                <button type="button" @click="go(current - 1)" aria-label="Banner trước"
                        class="absolute left-3 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/20 p-2 text-white backdrop-blur transition hover:bg-white/40 sm:block">
                    <x-icon name="chevron-left" class="h-6 w-6" />
                </button>
                <button type="button" @click="go(current + 1)" aria-label="Banner sau"
                        class="absolute right-3 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/20 p-2 text-white backdrop-blur transition hover:bg-white/40 sm:block">
                    <x-icon name="chevron-right" class="h-6 w-6" />
                </button>
                <div class="absolute inset-x-0 bottom-4 flex justify-center gap-2">
                    @foreach ($banners as $banner)
                        <button type="button" @click="go({{ $loop->index }})" aria-label="Xem banner {{ $loop->iteration }}"
                                :aria-current="current === {{ $loop->index }}"
                                :class="current === {{ $loop->index }} ? 'w-8 bg-white' : 'w-2.5 bg-white/50 hover:bg-white/80'"
                                class="h-2.5 rounded-full transition-all"></button>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-teal-500 text-white">
        <div class="absolute -right-20 -top-20 h-80 w-80 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-brand-900/20"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-20">
            <div>
                <span class="badge bg-white/15 text-white">🎓 Nền tảng học trực tuyến cho sinh viên</span>
                <h1 class="mt-4 text-3xl font-bold leading-tight sm:text-5xl">Nâng tầm kiến thức<br>cùng <span class="text-amber-300">UTH E-Learning</span></h1>
                <p class="mt-4 max-w-xl text-brand-50 sm:text-lg">Học lập trình, dữ liệu, ngoại ngữ và kỹ năng mềm với lộ trình rõ ràng, bài giảng video và theo dõi tiến độ từng bài.</p>

                <form action="{{ route('courses.index') }}" class="mt-8 flex max-w-lg flex-col gap-2 rounded-xl bg-white p-2 shadow-xl sm:flex-row">
                    <input type="search" name="q" placeholder="Bạn muốn học gì hôm nay?" class="flex-1 rounded-lg border-0 text-gray-900 focus:ring-2 focus:ring-brand-500">
                    <button class="btn-primary">
                        <x-icon name="search" class="h-4 w-4" /> Tìm khoá học
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-2 gap-4">
                @foreach ([
                    ['academic', $stats['courses'], 'Khoá học'],
                    ['users', $stats['students'], 'Học viên'],
                    ['user', $stats['teachers'], 'Giảng viên'],
                    ['clipboard', $stats['enrollments'], 'Lượt ghi danh'],
                ] as [$icon, $value, $label])
                    <div class="rounded-2xl bg-white/10 p-5 backdrop-blur transition hover:bg-white/20">
                        <x-icon :name="$icon" class="h-8 w-8 text-amber-300" />
                        <p class="mt-3 text-3xl font-bold">{{ number_format($value) }}+</p>
                        <p class="text-sm text-brand-50">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Quick access tiles, inspired by a student portal --}}
    <section class="mx-auto -mt-8 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['book', 'Khoá học', 'Khám phá thư viện', route('courses.index')],
                ['academic', 'Góc học tập', 'Tiến độ của bạn', route('dashboard')],
                ['bell', 'Thông báo', 'Tin mới nhất', route('announcements.index')],
                ['user', 'Hồ sơ', 'Thông tin cá nhân', route('profile.edit')],
            ] as [$icon, $label, $description, $url])
                <a href="{{ $url }}" class="card group flex flex-col items-center p-5 text-center transition hover:-translate-y-1 hover:border-brand-200">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-700 transition group-hover:bg-brand-700 group-hover:text-white">
                        <x-icon :name="$icon" class="h-6 w-6" />
                    </span>
                    <span class="mt-3 font-semibold text-gray-900">{{ $label }}</span>
                    <span class="text-xs text-gray-500">{{ $description }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Categories --}}
    <section class="mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Danh mục nổi bật</h2>
                <p class="mt-1 text-gray-500">Chọn lĩnh vực bạn quan tâm</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($categories as $category)
                <a href="{{ route('courses.index', ['category' => $category->slug]) }}" class="card flex items-center gap-3 p-3 transition hover:border-brand-300 hover:bg-brand-50 sm:p-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-white sm:h-10 sm:w-10">
                        <x-icon name="tag" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold leading-snug text-gray-900 sm:truncate sm:text-base">{{ $category->name }}</span>
                        <span class="text-xs text-gray-500">{{ $category->courses_count }} khoá học</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Featured courses --}}
    <section class="mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Khoá học được yêu thích</h2>
                <p class="mt-1 text-gray-500">Nhiều học viên lựa chọn nhất</p>
            </div>
            <a href="{{ route('courses.index') }}" class="hidden items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-900 sm:flex">
                Xem tất cả <x-icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($featuredCourses as $course)
                <x-course-card :course="$course" />
            @empty
                <div class="card col-span-full"><x-empty-state icon="book" title="Chưa có khoá học nào" /></div>
            @endforelse
        </div>

        <a href="{{ route('courses.index') }}" class="btn-secondary mt-5 w-full sm:hidden">
            Xem tất cả khoá học <x-icon name="arrow-right" class="h-4 w-4" />
        </a>
    </section>

    {{-- Announcements --}}
    <section class="mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between bg-brand-700 px-5 py-3 text-white">
                <h2 class="flex items-center gap-2 font-semibold"><x-icon name="bell" class="h-5 w-5" /> Thông báo mới</h2>
                <a href="{{ route('announcements.index') }}" class="text-sm text-brand-100 hover:text-white">Xem tất cả</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($announcements as $announcement)
                    <a href="{{ route('announcements.show', $announcement) }}" class="flex items-center gap-4 px-5 py-4 hover:bg-brand-50/50">
                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                            <span class="text-lg font-bold leading-none">{{ $announcement->published_at->format('d') }}</span>
                            <span class="text-[10px] uppercase">Th{{ $announcement->published_at->format('m') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-900">{{ $announcement->title }}</p>
                            <p class="truncate text-sm text-gray-500">{{ Str::limit(strip_tags($announcement->content), 120) }}</p>
                        </div>
                        <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-gray-400" />
                    </a>
                @empty
                    <x-empty-state icon="bell" title="Chưa có thông báo" />
                @endforelse
            </div>
        </div>
    </section>

    {{-- Call to action --}}
    @guest
        <section class="mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-6 rounded-2xl bg-gradient-to-r from-brand-800 to-brand-600 px-6 py-10 text-white sm:flex-row sm:px-10">
                <div>
                    <h2 class="text-2xl font-bold">Sẵn sàng bắt đầu hành trình học tập?</h2>
                    <p class="mt-1 text-brand-100">Tạo tài khoản miễn phí và ghi danh khoá học đầu tiên ngay hôm nay.</p>
                </div>
                <a href="{{ route('register') }}" class="btn bg-white px-6 py-3 text-brand-800 hover:bg-brand-50">Đăng ký ngay</a>
            </div>
        </section>
    @endguest
</x-app-layout>
