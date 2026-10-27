<x-admin-layout :title="$quiz->exists ? 'Sửa bài kiểm tra' : 'Thêm bài kiểm tra'">
    <a href="{{ route('instructor.courses.curriculum', $course) }}" class="mb-4 flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700"><x-icon name="chevron-left" class="h-4 w-4" /> {{ $course->title }}</a>

    <div class="grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ $quiz->exists ? route('instructor.courses.quizzes.update', [$course, $quiz]) : route('instructor.courses.quizzes.store', $course) }}" class="card h-fit space-y-5 p-6">
            @csrf
            @if ($quiz->exists) @method('PUT') @endif

            <div>
                <label for="title" class="form-label">Tên bài kiểm tra <span class="text-rose-500">*</span></label>
                <input id="title" name="title" value="{{ old('title', $quiz->title) }}" required maxlength="255" class="form-input">
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="section_id" class="form-label">Thuộc chương <span class="text-rose-500">*</span></label>
                <select id="section_id" name="section_id" required class="form-input">
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected((int) old('section_id', $quiz->section_id) === $section->id)>{{ $section->title }}</option>
                    @endforeach
                </select>
                @error('section_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="description" class="form-label">Mô tả / hướng dẫn</label>
                <textarea id="description" name="description" rows="3" maxlength="1000" class="form-input">{{ old('description', $quiz->description) }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="pass_percent" class="form-label">Điểm đạt (%)</label>
                <input id="pass_percent" type="number" min="0" max="100" name="pass_percent" value="{{ old('pass_percent', $quiz->pass_percent) }}" class="form-input">
                @error('pass_percent') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-2">
                <button class="btn-primary flex-1">{{ $quiz->exists ? 'Lưu thay đổi' : 'Tạo bài kiểm tra' }}</button>
                <a href="{{ route('instructor.courses.curriculum', $course) }}" class="btn-secondary">Huỷ</a>
            </div>
        </form>

        @if ($quiz->exists)
            <div class="card min-w-0 overflow-hidden xl:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-gray-900">Câu hỏi ({{ $quiz->questions->count() }})</h2>
                        <p class="text-xs text-gray-500">Mỗi câu có một đáp án đúng. Đã có {{ $quiz->attempts_count }} lượt làm bài.</p>
                    </div>
                    <a href="{{ route('instructor.courses.quizzes.questions.create', [$course, $quiz]) }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm câu hỏi</a>
                </div>
                <ol class="divide-y divide-gray-100">
                    @forelse ($quiz->questions as $question)
                        <li class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-medium text-gray-900">Câu {{ $loop->iteration }}. {{ $question->content }}</p>
                                <div class="flex shrink-0 items-center">
                                    <a href="{{ route('instructor.courses.quizzes.questions.edit', [$course, $quiz, $question]) }}" class="btn-ghost btn-sm" title="Sửa"><x-icon name="pencil" class="h-4 w-4" /></a>
                                    <x-delete-button :action="route('instructor.courses.quizzes.questions.destroy', [$course, $quiz, $question])" confirm="Xoá câu hỏi này?" />
                                </div>
                            </div>
                            <ul class="mt-2 grid gap-1 sm:grid-cols-2">
                                @foreach ($question->answers as $answer)
                                    <li @class(['flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm', 'bg-emerald-50 font-medium text-emerald-700' => $answer->is_correct, 'bg-gray-50 text-gray-600' => ! $answer->is_correct])>
                                        <x-icon :name="$answer->is_correct ? 'check-circle' : 'x'" class="h-4 w-4 shrink-0" /> {{ $answer->content }}
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @empty
                        <li><x-empty-state icon="clipboard" title="Chưa có câu hỏi nào" /></li>
                    @endforelse
                </ol>
            </div>
        @endif
    </div>
</x-admin-layout>
