<x-app-layout :title="$quiz->title">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
        <a href="{{ route('learn.show', $course) }}" class="flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700">
            <x-icon name="chevron-left" class="h-4 w-4" /> {{ $course->title }}
        </a>

        <div class="card mt-4 p-6">
            <p class="text-sm font-medium text-brand-700">{{ $quiz->section->title }}</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $quiz->title }}</h1>
            @if ($quiz->description)
                <p class="mt-2 text-gray-600">{{ $quiz->description }}</p>
            @endif
            <p class="mt-3 flex flex-wrap gap-4 text-sm text-gray-500">
                <span class="flex items-center gap-1"><x-icon name="clipboard" class="h-4 w-4" /> {{ $quiz->questions->count() }} câu hỏi</span>
                <span class="flex items-center gap-1"><x-icon name="trophy" class="h-4 w-4" /> Đạt từ {{ $quiz->pass_percent }}%</span>
            </p>
        </div>

        @if ($quiz->questions->isEmpty())
            <div class="card mt-6"><x-empty-state icon="clipboard" title="Bài kiểm tra chưa có câu hỏi" /></div>
        @else
            <form method="POST" action="{{ route('quizzes.submit', [$course, $quiz]) }}" class="mt-6 space-y-4" onsubmit="return confirm('Nộp bài kiểm tra?')">
                @csrf
                @foreach ($quiz->questions as $question)
                    <fieldset class="card p-5">
                        <legend class="sr-only">Câu {{ $loop->iteration }}</legend>
                        <p class="font-semibold text-gray-900">Câu {{ $loop->iteration }}. {{ $question->content }}</p>
                        <div class="mt-3 space-y-2">
                            @foreach ($question->answers as $answer)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-2.5 text-sm transition hover:border-brand-300 hover:bg-brand-50/40 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $answer->id }}" class="text-brand-600 focus:ring-brand-500">
                                    <span class="font-semibold text-gray-500">{{ chr(65 + $loop->index) }}.</span>
                                    <span class="text-gray-800">{{ $answer->content }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <div class="flex justify-end">
                    <button class="btn-primary px-6 py-3"><x-icon name="check" class="h-5 w-5" /> Nộp bài</button>
                </div>
            </form>
        @endif

        <div class="card mt-6 overflow-hidden">
            <h2 class="border-b border-gray-100 px-5 py-3 font-semibold text-gray-900">Lịch sử làm bài</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr><th>Lần</th><th>Thời gian</th><th class="text-center">Điểm</th><th>Kết quả</th><th class="text-right"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($attempts as $attempt)
                            @php($attempt->setRelation('quiz', $quiz))
                            <tr>
                                <td>#{{ $attempts->count() - $loop->index }}</td>
                                <td class="whitespace-nowrap">{{ $attempt->created_at->format('H:i d/m/Y') }}</td>
                                <td class="text-center font-semibold">{{ $attempt->score }}/{{ $attempt->total_questions }} ({{ $attempt->percent }}%)</td>
                                <td>
                                    @if ($attempt->isPassed())
                                        <span class="badge bg-emerald-100 text-emerald-700">Đạt</span>
                                    @else
                                        <span class="badge bg-rose-100 text-rose-700">Chưa đạt</span>
                                    @endif
                                </td>
                                <td class="text-right"><a href="{{ route('quizzes.result', [$course, $quiz, $attempt]) }}" class="text-sm font-medium text-brand-700">Xem lại</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-sm text-gray-500">Bạn chưa làm bài kiểm tra này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
