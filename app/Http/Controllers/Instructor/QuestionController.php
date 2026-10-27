<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\QuestionRequest;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function create(Course $course, Quiz $quiz): View
    {
        Gate::authorize('manage', $course);

        return view('instructor.questions.form', [
            'course' => $course,
            'quiz' => $quiz,
            'question' => new Question(['position' => (int) $quiz->questions()->max('position') + 1]),
            'answers' => array_fill(0, 4, ''),
            'correctAnswer' => 0,
        ]);
    }

    public function store(QuestionRequest $request, Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('manage', $course);

        DB::transaction(function () use ($request, $quiz): void {
            $question = $quiz->questions()->create([
                'content' => $request->validated('content'),
                'position' => $request->integer('position') ?: (int) $quiz->questions()->max('position') + 1,
            ]);

            $question->answers()->createMany($request->answerRows());
        });

        return to_route('instructor.courses.quizzes.edit', [$course, $quiz])->with('success', 'Đã thêm câu hỏi.');
    }

    public function edit(Course $course, Quiz $quiz, Question $question): View
    {
        Gate::authorize('manage', $course);

        $answers = $question->answers;

        return view('instructor.questions.form', [
            'course' => $course,
            'quiz' => $quiz,
            'question' => $question,
            'answers' => array_pad($answers->pluck('content')->all(), 4, ''),
            'correctAnswer' => max(0, (int) $answers->search(fn ($answer) => $answer->is_correct)),
        ]);
    }

    public function update(QuestionRequest $request, Course $course, Quiz $quiz, Question $question): RedirectResponse
    {
        Gate::authorize('manage', $course);

        DB::transaction(function () use ($request, $question): void {
            $question->update([
                'content' => $request->validated('content'),
                'position' => $request->integer('position', $question->position),
            ]);

            $question->answers()->delete();
            $question->answers()->createMany($request->answerRows());
        });

        return to_route('instructor.courses.quizzes.edit', [$course, $quiz])->with('success', 'Đã cập nhật câu hỏi.');
    }

    public function destroy(Course $course, Quiz $quiz, Question $question): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $question->delete();

        return back()->with('success', 'Đã xoá câu hỏi.');
    }
}
