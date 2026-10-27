<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuizAttemptController extends Controller
{
    /**
     * The quiz form, with the learner's previous attempts.
     */
    public function show(Request $request, Course $course, Quiz $quiz): View
    {
        Gate::authorize('learn', $course);

        return view('quizzes.show', [
            'course' => $course,
            'quiz' => $quiz->load(['section', 'questions.answers']),
            'attempts' => $quiz->attempts()->where('user_id', $request->user()->id)->latest()->get(),
        ]);
    }

    public function store(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('learn', $course);

        if (! $quiz->questions()->exists()) {
            return back()->with('error', 'Bài kiểm tra này chưa có câu hỏi.');
        }

        $validated = $request->validate(['answers' => ['nullable', 'array'], 'answers.*' => ['nullable', 'integer']]);

        $attempt = $quiz->grade($request->user(), $validated['answers'] ?? []);

        return to_route('quizzes.result', [$course, $quiz, $attempt]);
    }

    public function result(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): View
    {
        Gate::authorize('learn', $course);
        abort_unless($attempt->user_id === $request->user()->id, 403);

        return view('quizzes.result', [
            'course' => $course,
            'quiz' => $quiz->load('questions.answers'),
            'attempt' => $attempt->setRelation('quiz', $quiz),
        ]);
    }
}
