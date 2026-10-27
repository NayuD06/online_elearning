<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\QuizRequest;
use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function create(Request $request, Course $course): View
    {
        Gate::authorize('manage', $course);

        return view('instructor.quizzes.form', [
            'course' => $course,
            'sections' => $course->sections()->get(['id', 'title']),
            'quiz' => new Quiz([
                'section_id' => $request->integer('section') ?: $course->sections()->value('id'),
                'pass_percent' => 50,
            ]),
        ]);
    }

    public function store(QuizRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $quiz = $course->quizzes()->create($request->validated());

        return to_route('instructor.courses.quizzes.edit', [$course, $quiz])->with('success', 'Đã tạo bài kiểm tra. Hãy thêm câu hỏi.');
    }

    public function edit(Course $course, Quiz $quiz): View
    {
        Gate::authorize('manage', $course);

        return view('instructor.quizzes.form', [
            'course' => $course,
            'sections' => $course->sections()->get(['id', 'title']),
            'quiz' => $quiz->load('questions.answers')->loadCount('attempts'),
        ]);
    }

    public function update(QuizRequest $request, Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $quiz->update($request->validated());

        return back()->with('success', 'Đã cập nhật bài kiểm tra.');
    }

    public function destroy(Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $quiz->delete();

        return to_route('instructor.courses.curriculum', $course)->with('success', 'Đã xoá bài kiểm tra.');
    }
}
