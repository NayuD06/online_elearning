<x-admin-layout title="Hỏi đáp của học viên">
    <div class="card">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <x-search-bar placeholder="Tìm trong nội dung câu hỏi..." class="flex-1">
                <select name="status" class="form-input mt-0 sm:w-48">
                    <option value="unanswered" @selected($status === 'unanswered')>Chưa trả lời</option>
                    <option value="all" @selected($status === 'all')>Tất cả câu hỏi</option>
                </select>
            </x-search-bar>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse ($questions as $question)
                @php($lesson = $question->lesson)
                <div class="p-4 sm:p-5">
                    <p class="text-xs text-gray-500">
                        <span class="font-medium text-brand-700">{{ $lesson->course->title }}</span> · {{ $lesson->title }}
                    </p>
                    <div class="mt-2 flex gap-3">
                        <img src="{{ $question->user->avatar_url }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm"><span class="font-semibold text-gray-900">{{ $question->user->name }}</span> <span class="text-xs text-gray-400">{{ $question->created_at->diffForHumans() }}</span></p>
                            <p class="mt-1 whitespace-pre-line break-words text-sm text-gray-700">{{ $question->body }}</p>

                            @foreach ($question->answers as $answer)
                                <div class="mt-2 rounded-lg bg-brand-50/60 px-3 py-2 text-sm">
                                    <span class="font-semibold text-gray-900">{{ $answer->user->name }}:</span>
                                    <span class="whitespace-pre-line break-words text-gray-700">{{ $answer->body }}</span>
                                </div>
                            @endforeach

                            <form method="POST" action="{{ route('lesson-questions.answer', $question) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start">
                                @csrf
                                <textarea name="body" rows="2" required maxlength="2000" class="form-input mt-0 flex-1" placeholder="Nhập câu trả lời..."></textarea>
                                <button class="btn-primary shrink-0">Trả lời</button>
                            </form>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <a href="{{ route('learn.show', [$lesson->course, $lesson]) }}#hoi-dap-{{ $question->id }}" target="_blank" class="btn-ghost btn-sm" title="Mở bài học"><x-icon name="external" class="h-4 w-4" /></a>
                            <form method="POST" action="{{ route('lesson-questions.destroy', $question) }}" onsubmit="return confirm('Xoá câu hỏi này cùng các câu trả lời?')">
                                @csrf @method('DELETE')
                                <button class="btn-ghost btn-sm text-rose-600" title="Xoá câu hỏi"><x-icon name="trash" class="h-4 w-4" /></button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <x-empty-state icon="chat" :title="$status === 'unanswered' ? 'Không còn câu hỏi nào chờ trả lời' : 'Chưa có câu hỏi nào'" />
            @endforelse
        </div>
        <div class="p-4">{{ $questions->links() }}</div>
    </div>
</x-admin-layout>
