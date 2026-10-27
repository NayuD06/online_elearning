<div id="hoi-dap" class="card p-6">
    <h2 class="flex items-center gap-2 text-lg font-bold text-gray-900"><x-icon name="question" class="h-5 w-5 text-brand-600" /> Hỏi đáp về bài học</h2>

    @if ($canLearn)
        <form method="POST" action="{{ route('lesson-questions.store', [$course, $lesson]) }}" class="mt-4">
            @csrf
            <label for="question-body" class="sr-only">Câu hỏi của bạn</label>
            <textarea id="question-body" name="body" rows="3" required maxlength="2000" class="form-input" placeholder="Bạn chưa hiểu chỗ nào trong bài? Đặt câu hỏi cho giảng viên...">{{ old('body') }}</textarea>
            @error('body') <p class="form-error">{{ $message }}</p> @enderror
            <button class="btn-primary mt-3"><x-icon name="chat" class="h-4 w-4" /> Gửi câu hỏi</button>
        </form>
    @else
        <p class="mt-3 text-sm text-gray-500">Ghi danh khoá học để đặt câu hỏi cho giảng viên.</p>
    @endif

    <div class="mt-6 space-y-5">
        @forelse ($questions as $question)
            <div id="hoi-dap-{{ $question->id }}" class="rounded-xl border border-gray-100 p-4">
                <div class="flex gap-3">
                    <img src="{{ $question->user->avatar_url }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2 text-sm">
                            <span class="font-semibold text-gray-900">{{ $question->user->name }}</span>
                            <span class="text-xs text-gray-400">{{ $question->created_at->diffForHumans() }}</span>
                            @if ($question->answers->isEmpty())
                                <span class="badge bg-amber-100 text-amber-700">Chưa trả lời</span>
                            @endif
                        </div>
                        <p class="mt-1 whitespace-pre-line break-words text-sm text-gray-700">{{ $question->body }}</p>
                    </div>
                    @if ($question->user_id === Auth::id() || $canAnswer)
                        <form method="POST" action="{{ route('lesson-questions.destroy', $question) }}" onsubmit="return confirm('Xoá câu hỏi này cùng các câu trả lời?')">
                            @csrf @method('DELETE')
                            <button class="btn-ghost btn-sm text-rose-600" title="Xoá câu hỏi"><x-icon name="trash" class="h-4 w-4" /></button>
                        </form>
                    @endif
                </div>

                @foreach ($question->answers as $answer)
                    <div class="ml-12 mt-3 flex gap-3 rounded-lg bg-brand-50/60 p-3">
                        <img src="{{ $answer->user->avatar_url }}" alt="" class="h-8 w-8 shrink-0 rounded-full object-cover">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 text-sm">
                                <span class="font-semibold text-gray-900">{{ $answer->user->name }}</span>
                                <span class="badge {{ $answer->user->role->badgeClasses() }}">{{ $answer->user->role->label() }}</span>
                                <span class="text-xs text-gray-400">{{ $answer->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 whitespace-pre-line break-words text-sm text-gray-700">{{ $answer->body }}</p>
                        </div>
                        @if ($canAnswer)
                            <form method="POST" action="{{ route('lesson-questions.destroy', $answer) }}" onsubmit="return confirm('Xoá câu trả lời này?')">
                                @csrf @method('DELETE')
                                <button class="btn-ghost btn-sm text-rose-600" title="Xoá câu trả lời"><x-icon name="trash" class="h-4 w-4" /></button>
                            </form>
                        @endif
                    </div>
                @endforeach

                @if ($canAnswer)
                    <form method="POST" action="{{ route('lesson-questions.answer', $question) }}" class="ml-12 mt-3 flex flex-col gap-2 sm:flex-row sm:items-start">
                        @csrf
                        <textarea name="body" rows="2" required maxlength="2000" class="form-input mt-0 flex-1" placeholder="Trả lời học viên..."></textarea>
                        <button class="btn-secondary shrink-0">Trả lời</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500">Chưa có câu hỏi nào cho bài học này.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $questions->links() }}</div>
</div>
