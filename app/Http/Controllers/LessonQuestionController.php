<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Notifications\LessonQuestionAnswered;
use App\Notifications\NewLessonQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Q&A under a lesson: learners ask, the course instructor or an admin answers.
 */
class LessonQuestionController extends Controller
{
    public function store(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->course_id === $course->id, 404);
        Gate::authorize('learn', $course);

        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']], [], ['body' => 'câu hỏi']);

        $question = $lesson->questions()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $instructor = $course->instructor;
        if ($instructor && $instructor->id !== $request->user()->id) {
            $instructor->notify(new NewLessonQuestion($question));
        }

        return redirect()->to(route('learn.show', [$course, $lesson]).'#hoi-dap-'.$question->id)
            ->with('success', 'Đã gửi câu hỏi. Giảng viên sẽ trả lời sớm.');
    }

    public function answer(Request $request, LessonQuestion $question): RedirectResponse
    {
        abort_if($question->isAnswer(), 404);

        $lesson = $question->lesson;
        Gate::authorize('manage', $lesson->course);

        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']], [], ['body' => 'câu trả lời']);

        $question->answers()->create([
            'lesson_id' => $lesson->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        if ($question->user_id !== $request->user()->id) {
            try {
                $question->user->notify(new LessonQuestionAnswered($question));
            } catch (Throwable $e) {
                // The answer is saved even when the email cannot be sent.
                report($e);
            }
        }

        return back()->with('success', 'Đã gửi câu trả lời.');
    }

    public function destroy(Request $request, LessonQuestion $question): RedirectResponse
    {
        $course = $question->lesson->course;

        abort_unless($question->user_id === $request->user()->id || $request->user()->can('manage', $course), 403);

        $question->delete();

        return back()->with('success', $question->isAnswer() ? 'Đã xoá câu trả lời.' : 'Đã xoá câu hỏi.');
    }
}
