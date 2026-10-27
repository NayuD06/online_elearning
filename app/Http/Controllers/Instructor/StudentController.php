<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Learners enrolled in one course, with their progress.
     */
    public function __invoke(Request $request, Course $course): View
    {
        Gate::authorize('manage', $course);

        $lessonIds = $course->lessons()->pluck('id');
        $quizIds = $course->quizzes()->pluck('id');

        $enrollments = $course->enrollments()
            ->with('user')
            ->when($request->string('q')->toString(), fn ($query, string $keyword) => $query->whereHas(
                'user', fn ($query) => $query->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $enrollments->getCollection()->each(function (Enrollment $enrollment) use ($lessonIds, $quizIds): void {
            $completedLessons = $enrollment->user->completedLessons()->whereIn('lessons.id', $lessonIds)->count();

            $enrollment->setAttribute('progress', $lessonIds->isEmpty() ? 0 : (int) round($completedLessons / $lessonIds->count() * 100));
            $enrollment->setAttribute('completed_lessons', $completedLessons);
            $enrollment->setAttribute('quiz_attempts', $enrollment->user->quizAttempts()->whereIn('quiz_id', $quizIds)->count());
        });

        return view('instructor.courses.students', [
            'course' => $course,
            'enrollments' => $enrollments,
            'totalLessons' => $lessonIds->count(),
        ]);
    }
}
