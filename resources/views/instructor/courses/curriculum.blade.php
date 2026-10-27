<x-admin-layout :title="'Chương & bài học: '.$course->title">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('instructor.courses.edit', $course) }}" class="flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700"><x-icon name="chevron-left" class="h-4 w-4" /> Về thông tin khoá học</a>
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge {{ $course->status->badgeClasses() }}">{{ $course->status->label() }}</span>
            @if ($course->status->canBeSubmitted())
                <form method="POST" action="{{ route('instructor.courses.submit', $course) }}">
                    @csrf
                    <button class="btn-primary"><x-icon name="check-circle" class="h-4 w-4" /> Gửi duyệt</button>
                </form>
            @endif
        </div>
    </div>

    @if ($course->status === App\Enums\CourseStatus::Rejected && $course->rejection_reason)
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <strong>Khoá học bị từ chối:</strong> {{ $course->rejection_reason }} Hãy chỉnh sửa rồi gửi duyệt lại.
        </div>
    @endif

    <form method="POST" action="{{ route('instructor.courses.sections.store', $course) }}" class="card mb-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <label for="new_section_title" class="form-label">Thêm chương mới</label>
            <input id="new_section_title" name="title" required maxlength="255" placeholder="VD: Chương 1: Giới thiệu" class="form-input">
            @error('title') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <button class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm chương</button>
    </form>

    <div class="space-y-6">
        @forelse ($sections as $section)
            <div class="card overflow-hidden" x-data="{ editing: false }">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-gray-50 px-4 py-3">
                    <div x-show="! editing" class="flex min-w-0 items-center gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">{{ $loop->iteration }}</span>
                        <h2 class="truncate font-semibold text-gray-900">{{ $section->title }}</h2>
                        <span class="text-xs text-gray-500">{{ $section->lessons->count() }} bài · {{ $section->quizzes->count() }} quiz</span>
                    </div>
                    <form x-show="editing" x-cloak method="POST" action="{{ route('instructor.courses.sections.update', [$course, $section]) }}" class="flex flex-1 flex-wrap items-center gap-2">
                        @csrf @method('PUT')
                        <input name="title" value="{{ $section->title }}" required maxlength="255" class="form-input mt-0 min-w-0 flex-1">
                        <input name="position" type="number" min="0" value="{{ $section->position }}" class="form-input mt-0 w-20" title="Thứ tự">
                        <button class="btn-primary btn-sm">Lưu</button>
                        <button type="button" @click="editing = false" class="btn-ghost btn-sm">Huỷ</button>
                    </form>
                    <div x-show="! editing" class="flex flex-wrap items-center gap-1">
                        <a href="{{ route('instructor.courses.lessons.create', [$course, 'section' => $section->id]) }}" class="btn-secondary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Bài học</a>
                        <a href="{{ route('instructor.courses.quizzes.create', [$course, 'section' => $section->id]) }}" class="btn-secondary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Quiz</a>
                        <button type="button" @click="editing = true" class="btn-ghost btn-sm" title="Sửa chương"><x-icon name="pencil" class="h-4 w-4" /></button>
                        <x-delete-button :action="route('instructor.courses.sections.destroy', [$course, $section])" confirm="Xoá chương này cùng toàn bộ bài học và quiz bên trong?" />
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr><th class="w-16 text-center">Thứ tự</th><th>Nội dung</th><th>Loại</th><th class="text-center">Thời lượng / Câu hỏi</th><th class="text-center">Học thử</th><th class="text-right">Thao tác</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($section->lessons as $lesson)
                                <tr>
                                    <td class="text-center font-semibold text-brand-700">{{ $lesson->position }}</td>
                                    <td class="font-medium text-gray-900">{{ $lesson->title }}</td>
                                    <td>
                                        @if ($lesson->video_url)
                                            <span class="flex items-center gap-1 text-brand-700"><x-icon name="play" class="h-4 w-4" /> Bài học video</span>
                                        @else
                                            <span class="flex items-center gap-1 text-gray-600"><x-icon name="book" class="h-4 w-4" /> Bài học</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $lesson->duration_minutes }} phút</td>
                                    <td class="text-center">
                                        @if ($lesson->is_preview)
                                            <span class="badge bg-amber-100 text-amber-700">Có</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-right">
                                        <a href="{{ route('learn.show', [$course, $lesson]) }}" target="_blank" class="btn-ghost btn-sm" title="Xem"><x-icon name="eye" class="h-4 w-4" /></a>
                                        <a href="{{ route('instructor.courses.lessons.edit', [$course, $lesson]) }}" class="btn-ghost btn-sm" title="Sửa"><x-icon name="pencil" class="h-4 w-4" /></a>
                                        <x-delete-button :action="route('instructor.courses.lessons.destroy', [$course, $lesson])" />
                                    </td>
                                </tr>
                            @endforeach
                            @foreach ($section->quizzes as $quiz)
                                <tr class="bg-amber-50/40">
                                    <td class="text-center text-gray-400">—</td>
                                    <td class="font-medium text-gray-900">{{ $quiz->title }}</td>
                                    <td><span class="flex items-center gap-1 text-amber-700"><x-icon name="clipboard" class="h-4 w-4" /> Quiz (đạt {{ $quiz->pass_percent }}%)</span></td>
                                    <td class="text-center">{{ $quiz->questions_count }} câu</td>
                                    <td class="text-center text-gray-400">—</td>
                                    <td class="whitespace-nowrap text-right">
                                        <a href="{{ route('instructor.courses.quizzes.edit', [$course, $quiz]) }}" class="btn-ghost btn-sm" title="Sửa quiz & câu hỏi"><x-icon name="pencil" class="h-4 w-4" /></a>
                                        <x-delete-button :action="route('instructor.courses.quizzes.destroy', [$course, $quiz])" confirm="Xoá bài kiểm tra này cùng câu hỏi và lịch sử làm bài?" />
                                    </td>
                                </tr>
                            @endforeach
                            @if ($section->lessons->isEmpty() && $section->quizzes->isEmpty())
                                <tr><td colspan="6"><x-empty-state icon="book" title="Chương này chưa có bài học" /></td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card"><x-empty-state icon="list" title="Khoá học chưa có chương nào. Hãy thêm chương đầu tiên ở trên." /></div>
        @endforelse

        @if ($lessonsWithoutSection->isNotEmpty())
            <div class="card overflow-hidden">
                <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">
                    <h2 class="font-semibold text-gray-900">Bài học chưa xếp vào chương</h2>
                    <p class="text-xs text-gray-500">Mở từng bài và chọn chương cho bài học.</p>
                </div>
                <ul class="divide-y divide-gray-100">
                    @foreach ($lessonsWithoutSection as $lesson)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <span class="font-medium text-gray-900">{{ $lesson->title }}</span>
                            <a href="{{ route('instructor.courses.lessons.edit', [$course, $lesson]) }}" class="btn-ghost btn-sm" title="Sửa"><x-icon name="pencil" class="h-4 w-4" /></a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-admin-layout>
