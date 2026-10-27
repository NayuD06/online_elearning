<x-app-layout :title="'Kết quả: '.$quiz->title">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
        <a href="{{ route('quizzes.show', [$course, $quiz]) }}" class="flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700">
            <x-icon name="chevron-left" class="h-4 w-4" /> {{ $quiz->title }}
        </a>

        @php($passed = $attempt->isPassed())
        <div @class(['card mt-4 flex flex-col items-center gap-2 p-8 text-center', 'border-t-4 border-emerald-500' => $passed, 'border-t-4 border-rose-500' => ! $passed])>
            <x-icon :name="$passed ? 'trophy' : 'clipboard'" @class(['h-12 w-12', 'text-emerald-500' => $passed, 'text-rose-400' => ! $passed]) />
            <p class="text-4xl font-bold text-gray-900">{{ $attempt->score }}/{{ $attempt->total_questions }}</p>
            <p class="text-lg font-semibold {{ $passed ? 'text-emerald-700' : 'text-rose-600' }}">{{ $attempt->percent }}% · {{ $passed ? 'Đạt' : 'Chưa đạt' }} (cần {{ $quiz->pass_percent }}%)</p>
            <p class="text-sm text-gray-500">Nộp lúc {{ $attempt->created_at->format('H:i d/m/Y') }}</p>
            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <a href="{{ route('quizzes.show', [$course, $quiz]) }}" class="btn-primary">Làm lại</a>
                <a href="{{ route('learn.show', $course) }}" class="btn-secondary">Tiếp tục học</a>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            @foreach ($quiz->questions as $question)
                @php($chosenId = $attempt->answers[$question->id] ?? null)
                <div class="card p-5">
                    <p class="font-semibold text-gray-900">Câu {{ $loop->iteration }}. {{ $question->content }}</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($question->answers as $answer)
                            <li @class([
                                'flex items-center gap-3 rounded-lg border px-4 py-2.5 text-sm',
                                'border-emerald-300 bg-emerald-50 text-emerald-800' => $answer->is_correct,
                                'border-rose-300 bg-rose-50 text-rose-700' => ! $answer->is_correct && $answer->id === $chosenId,
                                'border-gray-200 text-gray-700' => ! $answer->is_correct && $answer->id !== $chosenId,
                            ])>
                                <x-icon :name="$answer->is_correct ? 'check-circle' : ($answer->id === $chosenId ? 'x' : 'chevron-right')" class="h-4 w-4 shrink-0" />
                                <span class="flex-1">{{ $answer->content }}</span>
                                @if ($answer->id === $chosenId)
                                    <span class="text-xs font-semibold">Bạn chọn</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if (! $chosenId)
                        <p class="mt-2 text-xs text-rose-600">Bạn chưa trả lời câu này.</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
