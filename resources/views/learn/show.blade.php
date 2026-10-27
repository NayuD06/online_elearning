<x-app-layout :title="$lesson->title">
    <div x-data="{ outline: false }" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('courses.show', $course) }}" class="flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700">
                    <x-icon name="chevron-left" class="h-4 w-4" /> {{ $course->title }}
                </a>
                <div class="mt-2 flex items-center gap-3">
                    <div class="h-2 w-48 overflow-hidden rounded-full bg-gray-200">
                        <div class="h-full rounded-full bg-brand-600" style="width: {{ $progress }}%"></div>
                    </div>
                    <span class="text-sm font-semibold text-gray-700">Hoàn thành {{ $progress }}%</span>
                </div>
            </div>
            <button @click="outline = ! outline" class="btn-secondary lg:hidden"><x-icon name="list" class="h-4 w-4" /> Danh sách bài</button>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="card overflow-hidden">
                    @if ($lesson->embed_url)
                        <iframe src="{{ $lesson->embed_url }}" title="{{ $lesson->title }}" class="aspect-video w-full bg-gray-900" allowfullscreen
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                    @elseif ($lesson->video_url)
                        <div class="flex aspect-video items-center justify-center bg-gray-900">
                            <a href="{{ $lesson->video_url }}" target="_blank" rel="noopener" class="btn bg-white text-gray-900"><x-icon name="play" class="h-5 w-5" /> Mở video bài giảng</a>
                        </div>
                    @endif

                    <div class="p-6">
                        <p class="text-sm font-medium text-brand-700">Bài {{ $lessons->search(fn ($item) => $item->id === $lesson->id) + 1 }} / {{ $lessons->count() }}</p>
                        <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $lesson->title }}</h1>
                        <p class="mt-1 flex items-center gap-1 text-sm text-gray-500"><x-icon name="clock" class="h-4 w-4" /> {{ $lesson->duration_minutes }} phút</p>
                        <div class="rich-content mt-6">{!! \App\Support\HtmlSanitizer::clean($lesson->content) !!}</div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    @if ($previousLesson)
                        <a href="{{ route('learn.show', [$course, $previousLesson]) }}" class="btn-secondary"><x-icon name="chevron-left" class="h-4 w-4" /> Bài trước</a>
                    @else
                        <span class="hidden sm:block"></span>
                    @endif

                    @if ($canLearn)
                        <form method="POST" action="{{ route('learn.complete', [$course, $lesson]) }}">
                            @csrf
                            @if ($completedLessonIds->contains($lesson->id))
                                <button class="btn w-full bg-emerald-100 text-emerald-700 hover:bg-emerald-200 sm:w-auto"><x-icon name="check-circle" class="h-5 w-5" /> Đã hoàn thành (bỏ đánh dấu)</button>
                            @else
                                <button class="btn-primary w-full sm:w-auto"><x-icon name="check" class="h-5 w-5" /> Đánh dấu hoàn thành</button>
                            @endif
                        </form>
                    @else
                        <form method="POST" action="{{ route('enrollments.store', $course) }}">
                            @csrf
                            <button class="btn-primary w-full sm:w-auto"><x-icon name="academic" class="h-5 w-5" /> Ghi danh để học tiếp</button>
                        </form>
                    @endif

                    @if ($nextLesson && ($canLearn || $nextLesson->is_preview))
                        <a href="{{ route('learn.show', [$course, $nextLesson]) }}" class="btn-secondary">Bài tiếp <x-icon name="chevron-right" class="h-4 w-4" /></a>
                    @endif
                </div>
            </div>

            {{-- On mobile the outline opens right under its toggle button instead of below the lesson --}}
            <aside :class="outline ? 'block' : 'hidden'" class="order-first lg:order-none lg:block">
                <div class="card overflow-hidden lg:sticky lg:top-28">
                    <h2 class="border-b border-gray-100 px-5 py-3 font-semibold text-gray-900">Nội dung khoá học</h2>
                    <div class="max-h-[50vh] overflow-y-auto lg:max-h-[70vh]">
                        @foreach ($outline as $group)
                            @if ($group['title'])
                                <p class="border-b border-gray-100 bg-gray-50 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $group['title'] }}</p>
                            @endif
                            <ol class="divide-y divide-gray-50">
                                @foreach ($group['lessons'] as $item)
                                    @php($isDone = $completedLessonIds->contains($item->id))
                                    <li>
                                        @if ($canLearn || $item->is_preview)
                                            <a href="{{ route('learn.show', [$course, $item]) }}"
                                               @class(['flex items-start gap-3 px-5 py-3 text-sm transition', 'bg-brand-50 font-semibold text-brand-800' => $item->id === $lesson->id, 'hover:bg-gray-50' => $item->id !== $lesson->id])>
                                                <span @class(['mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border', 'border-emerald-500 bg-emerald-500 text-white' => $isDone, 'border-gray-300 text-transparent' => ! $isDone])>
                                                    <x-icon name="check" class="h-3 w-3" />
                                                </span>
                                                <span class="flex-1">{{ $item->title }}</span>
                                                <span class="shrink-0 text-xs text-gray-400">{{ $item->duration_minutes }}'</span>
                                            </a>
                                        @else
                                            <span class="flex items-start gap-3 px-5 py-3 text-sm text-gray-400">
                                                <x-icon name="lock" class="mt-0.5 h-5 w-5 shrink-0" />
                                                <span class="flex-1">{{ $item->title }}</span>
                                                <span class="shrink-0 text-xs">{{ $item->duration_minutes }}'</span>
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                                @foreach ($group['quizzes'] as $quiz)
                                    <li>
                                        @if ($canLearn)
                                            <a href="{{ route('quizzes.show', [$course, $quiz]) }}" class="flex items-start gap-3 px-5 py-3 text-sm text-amber-800 transition hover:bg-amber-50">
                                                <x-icon name="clipboard" class="mt-0.5 h-5 w-5 shrink-0" />
                                                <span class="flex-1">{{ $quiz->title }}</span>
                                                <span class="shrink-0 text-xs">Quiz</span>
                                            </a>
                                        @else
                                            <span class="flex items-start gap-3 px-5 py-3 text-sm text-gray-400">
                                                <x-icon name="lock" class="mt-0.5 h-5 w-5 shrink-0" />
                                                <span class="flex-1">{{ $quiz->title }}</span>
                                                <span class="shrink-0 text-xs">Quiz</span>
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
