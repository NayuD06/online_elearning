@php
    $gallery = collect(array_filter([$course->thumbnail_url]))->merge($course->images->pluck('url'))->values();
    $totalMinutes = $course->lessons->sum('duration_minutes');
    $unsortedLessons = $course->lessons->whereNull('section_id');
    $lessonNumber = 1;
@endphp

<x-app-layout :title="$course->title">
    @unless ($course->isPublished())
        <div class="bg-amber-100 px-4 py-2 text-center text-sm text-amber-800">
            Khoá học đang ở trạng thái <strong>{{ $course->status->label() }}</strong> nên chưa hiển thị công khai. Chỉ giảng viên phụ trách và quản trị viên xem được trang này.
        </div>
    @endunless
    <section class="bg-gradient-to-br from-brand-800 to-brand-600 text-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div class="lg:col-span-2">
                <nav class="text-sm text-brand-100">
                    <a href="{{ route('courses.index') }}" class="hover:text-white">Khoá học</a>
                    @if ($course->category)
                        / <a href="{{ route('courses.index', ['category' => $course->category->slug]) }}" class="hover:text-white">{{ $course->category->name }}</a>
                    @endif
                </nav>
                <h1 class="mt-3 text-2xl font-bold sm:text-4xl">{{ $course->title }}</h1>
                <p class="mt-3 text-brand-50 sm:text-lg">{{ $course->summary }}</p>

                <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                    <span class="flex items-center gap-1.5">
                        <span class="font-semibold text-amber-300">{{ number_format((float) $course->reviews_avg_rating, 1) }}</span>
                        <x-stars :rating="$course->reviews_avg_rating" />
                    </span>
                    <span class="flex items-center gap-1"><x-icon name="users" class="h-4 w-4" /> {{ $course->enrollments_count }} học viên</span>
                    <span class="flex items-center gap-1"><x-icon name="book" class="h-4 w-4" /> {{ $course->lessons->count() }} bài học</span>
                    <span class="flex items-center gap-1"><x-icon name="clock" class="h-4 w-4" /> {{ intdiv($totalMinutes, 60) }} giờ {{ $totalMinutes % 60 }} phút</span>
                    <span class="badge bg-white/15 text-white">{{ $course->level->label() }}</span>
                </div>

                <div class="mt-5 flex items-center gap-3">
                    <img src="{{ $course->instructor->avatar_url }}" alt="" class="h-10 w-10 rounded-full border-2 border-white/50 object-cover">
                    <div class="text-sm">
                        <p class="text-brand-100">Giảng viên</p>
                        <p class="font-semibold">{{ $course->instructor->name }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-3 lg:px-8">
        <div class="space-y-8 lg:col-span-2">
            {{-- Gallery (multiple images) --}}
            @if ($gallery->isNotEmpty())
                <div x-data="{ active: 0, images: @js($gallery) }" class="card overflow-hidden">
                    <img :src="images[active]" alt="{{ $course->title }}" class="aspect-video w-full bg-gray-100 object-cover">
                    @if ($gallery->count() > 1)
                        <div class="flex gap-2 overflow-x-auto p-3">
                            <template x-for="(image, index) in images" :key="index">
                                <button type="button" @click="active = index" :class="active === index ? 'ring-2 ring-brand-600' : 'opacity-70 hover:opacity-100'" class="h-16 w-24 shrink-0 overflow-hidden rounded-lg">
                                    <img :src="image" alt="" class="h-full w-full object-cover">
                                </button>
                            </template>
                        </div>
                    @endif
                </div>
            @endif

            <div class="card p-6">
                <h2 class="text-xl font-bold text-gray-900">Mô tả khoá học</h2>
                <div class="rich-content mt-4">{!! \App\Support\HtmlSanitizer::clean($course->description) !!}</div>
            </div>

            {{-- Curriculum --}}
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h2 class="text-xl font-bold text-gray-900">Nội dung khoá học</h2>
                    <span class="text-sm text-gray-500">{{ $course->lessons->count() }} bài · {{ $totalMinutes }} phút</span>
                </div>
                @forelse ($course->sections as $section)
                    <div class="border-b border-gray-100 bg-gray-50 px-6 py-3">
                        <h3 class="font-semibold text-gray-900">{{ $section->title }}</h3>
                        <p class="text-xs text-gray-500">{{ $section->lessons->count() }} bài · {{ $section->lessons->sum('duration_minutes') }} phút{{ $section->quizzes->isNotEmpty() ? ' · '.$section->quizzes->count().' bài kiểm tra' : '' }}</p>
                    </div>
                    <ol class="divide-y divide-gray-100">
                        @foreach ($section->lessons as $lesson)
                                <li class="flex items-center gap-4 px-6 py-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700">{{ $lessonNumber++ }}</span>
                                    <span class="min-w-0 flex-1">
                                        @if ($isEnrolled || $lesson->is_preview)
                                            <a href="{{ route('learn.show', [$course, $lesson]) }}" class="font-medium text-gray-800 hover:text-brand-700">{{ $lesson->title }}</a>
                                        @else
                                            <span class="font-medium text-gray-800">{{ $lesson->title }}</span>
                                        @endif
                                        @if ($lesson->is_preview)
                                            <span class="badge ml-2 bg-amber-100 text-amber-700">Học thử</span>
                                        @endif
                                    </span>
                                    <span class="flex shrink-0 items-center gap-1 text-sm text-gray-500">
                                        <x-icon :name="$isEnrolled || $lesson->is_preview ? 'play' : 'lock'" class="h-4 w-4" />
                                        {{ $lesson->duration_minutes }}'
                                    </span>
                                </li>
                        @endforeach
                        @foreach ($section->quizzes as $quiz)
                            <li class="flex items-center gap-4 px-6 py-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-700"><x-icon name="clipboard" class="h-4 w-4" /></span>
                                <span class="min-w-0 flex-1">
                                    @if ($isEnrolled)
                                        <a href="{{ route('quizzes.show', [$course, $quiz]) }}" class="font-medium text-gray-800 hover:text-brand-700">{{ $quiz->title }}</a>
                                    @else
                                        <span class="font-medium text-gray-800">{{ $quiz->title }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-sm text-gray-500">Trắc nghiệm</span>
                            </li>
                        @endforeach
                    </ol>
                @empty
                    @if ($unsortedLessons->isEmpty())
                        <x-empty-state icon="book" title="Bài học đang được cập nhật" />
                    @endif
                @endforelse
                @if ($unsortedLessons->isNotEmpty())
                    <ol class="divide-y divide-gray-100">
                        @foreach ($unsortedLessons as $lesson)
                                <li class="flex items-center gap-4 px-6 py-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700">{{ $lessonNumber++ }}</span>
                                    <span class="min-w-0 flex-1">
                                        @if ($isEnrolled || $lesson->is_preview)
                                            <a href="{{ route('learn.show', [$course, $lesson]) }}" class="font-medium text-gray-800 hover:text-brand-700">{{ $lesson->title }}</a>
                                        @else
                                            <span class="font-medium text-gray-800">{{ $lesson->title }}</span>
                                        @endif
                                        @if ($lesson->is_preview)
                                            <span class="badge ml-2 bg-amber-100 text-amber-700">Học thử</span>
                                        @endif
                                    </span>
                                    <span class="flex shrink-0 items-center gap-1 text-sm text-gray-500">
                                        <x-icon :name="$isEnrolled || $lesson->is_preview ? 'play' : 'lock'" class="h-4 w-4" />
                                        {{ $lesson->duration_minutes }}'
                                    </span>
                                </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            {{-- Reviews --}}
            <div id="reviews" class="card p-6">
                <h2 class="text-xl font-bold text-gray-900">Đánh giá của học viên</h2>

                @if ($isEnrolled)
                    <form method="POST" action="{{ route('reviews.store', $course) }}" x-data="{ rating: {{ old('rating', $userReview?->rating ?? 5) }} }" class="mt-4 rounded-xl bg-brand-50/60 p-4">
                        @csrf
                        <p class="text-sm font-medium text-gray-700">{{ $userReview ? 'Cập nhật đánh giá của bạn' : 'Chia sẻ cảm nhận của bạn' }}</p>
                        <div class="mt-2 flex gap-1">
                            @for ($star = 1; $star <= 5; $star++)
                                <button type="button" @click="rating = {{ $star }}" aria-label="{{ $star }} sao">
                                    <svg :class="rating >= {{ $star }} ? 'text-amber-400' : 'text-gray-300'" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                </button>
                            @endfor
                            <input type="hidden" name="rating" :value="rating">
                        </div>
                        <textarea name="comment" rows="3" class="form-input mt-3" placeholder="Bạn thấy khoá học thế nào?">{{ old('comment', $userReview?->comment) }}</textarea>
                        @error('comment') <p class="form-error">{{ $message }}</p> @enderror
                        <button class="btn-primary mt-3">Gửi đánh giá</button>
                    </form>
                @endif

                <div class="mt-6 space-y-5">
                    @forelse ($reviews as $review)
                        <div class="flex gap-3">
                            <img src="{{ $review->user->avatar_url }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $review->user->name }}</p>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <x-stars :rating="$review->rating" class="h-3.5" /> {{ $review->created_at->diffForHumans() }}
                                </div>
                                @if ($review->comment)
                                    <p class="mt-1 text-sm text-gray-700">{{ $review->comment }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Chưa có đánh giá nào cho khoá học này.</p>
                    @endforelse
                </div>

                <div class="mt-6">{{ $reviews->fragment('reviews')->links() }}</div>
            </div>
        </div>

        {{-- Sticky enrol box --}}
        <aside class="order-first lg:order-none lg:col-span-1">
            <div class="card sticky top-28 overflow-hidden lg:-mt-48">
                <x-course-thumbnail :course="$course" class="aspect-video w-full" />
                <div class="p-6">
                    <p @class(['text-3xl font-bold', 'text-emerald-600' => $course->isFree(), 'text-brand-800' => ! $course->isFree()])>{{ $course->price_label }}</p>

                    @auth
                        @if ($isEnrolled)
                            <a href="{{ route('learn.show', $course) }}" class="btn-primary mt-4 w-full py-3">
                                <x-icon name="play" class="h-5 w-5" /> Vào học ngay
                            </a>
                            <form method="POST" action="{{ route('enrollments.destroy', $course) }}" onsubmit="return confirm('Bạn chắc chắn muốn huỷ ghi danh? Tiến độ học sẽ được giữ lại.')">
                                @csrf
                                @method('DELETE')
                                <button class="btn-ghost mt-2 w-full text-rose-600">Huỷ ghi danh</button>
                            </form>
                        @elseif ($course->isPublished())
                            <form method="POST" action="{{ route('enrollments.store', $course) }}">
                                @csrf
                                <button class="btn-primary mt-4 w-full py-3">
                                    <x-icon name="academic" class="h-5 w-5" /> Ghi danh khoá học
                                </button>
                            </form>
                        @endif
                        @can('manage', $course)
                            @if (Auth::user()->isAdmin())
                                <a href="{{ route('admin.courses.index', ['q' => $course->title]) }}" class="btn-secondary mt-2 w-full">
                                    <x-icon name="cog" class="h-4 w-4" /> Kiểm duyệt khoá học
                                </a>
                            @else
                                <a href="{{ route('instructor.courses.edit', $course) }}" class="btn-secondary mt-2 w-full">
                                    <x-icon name="pencil" class="h-4 w-4" /> Chỉnh sửa khoá học
                                </a>
                            @endif
                        @endcan
                    @else
                        <a href="{{ route('login') }}" class="btn-primary mt-4 w-full py-3">Đăng nhập để ghi danh</a>
                    @endauth

                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><x-icon name="book" class="h-4 w-4 text-brand-600" /> {{ $course->lessons->count() }} bài học</li>
                        <li class="flex items-center gap-2"><x-icon name="clock" class="h-4 w-4 text-brand-600" /> Tổng thời lượng {{ $totalMinutes }} phút</li>
                        <li class="flex items-center gap-2"><x-icon name="chart" class="h-4 w-4 text-brand-600" /> Trình độ {{ Str::lower($course->level->label()) }}</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" class="h-4 w-4 text-brand-600" /> Theo dõi tiến độ từng bài</li>
                    </ul>
                </div>
            </div>
        </aside>
    </div>

    @if ($relatedCourses->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-xl font-bold text-gray-900">Khoá học cùng danh mục</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($relatedCourses as $relatedCourse)
                    <x-course-card :course="$relatedCourse" />
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
