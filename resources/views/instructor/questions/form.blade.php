<x-admin-layout :title="$question->exists ? 'Sửa câu hỏi' : 'Thêm câu hỏi'">
    <a href="{{ route('instructor.courses.quizzes.edit', [$course, $quiz]) }}" class="mb-4 flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700"><x-icon name="chevron-left" class="h-4 w-4" /> {{ $quiz->title }}</a>

    <form method="POST" action="{{ $question->exists ? route('instructor.courses.quizzes.questions.update', [$course, $quiz, $question]) : route('instructor.courses.quizzes.questions.store', [$course, $quiz]) }}" class="card max-w-3xl space-y-5 p-6">
        @csrf
        @if ($question->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-[1fr_8rem]">
            <div>
                <label for="content" class="form-label">Nội dung câu hỏi <span class="text-rose-500">*</span></label>
                <textarea id="content" name="content" rows="3" required maxlength="2000" class="form-input">{{ old('content', $question->content) }}</textarea>
                @error('content') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="position" class="form-label">Thứ tự</label>
                <input id="position" type="number" min="0" name="position" value="{{ old('position', $question->position) }}" class="form-input">
            </div>
        </div>

        <fieldset>
            <legend class="form-label">Đáp án (chọn đáp án đúng)</legend>
            <div class="mt-2 space-y-2">
                @foreach (old('answers', $answers) as $index => $answer)
                    <label class="flex items-center gap-3">
                        <input type="radio" name="correct_answer" value="{{ $index }}" @checked((int) old('correct_answer', $correctAnswer) === $index) class="text-brand-600 focus:ring-brand-500">
                        <span class="w-6 text-sm font-semibold text-gray-500">{{ chr(65 + $index) }}.</span>
                        <input name="answers[{{ $index }}]" value="{{ $answer }}" maxlength="500" placeholder="Đáp án {{ chr(65 + $index) }}{{ $index > 1 ? ' (không bắt buộc)' : '' }}" class="form-input mt-0 flex-1">
                    </label>
                @endforeach
            </div>
            @error('answers') <p class="form-error">{{ $message }}</p> @enderror
            @error('answers.*') <p class="form-error">{{ $message }}</p> @enderror
            @error('correct_answer') <p class="form-error">{{ $message }}</p> @enderror
        </fieldset>

        <div class="flex gap-2">
            <button class="btn-primary">{{ $question->exists ? 'Lưu thay đổi' : 'Thêm câu hỏi' }}</button>
            <a href="{{ route('instructor.courses.quizzes.edit', [$course, $quiz]) }}" class="btn-secondary">Huỷ</a>
        </div>
    </form>
</x-admin-layout>
