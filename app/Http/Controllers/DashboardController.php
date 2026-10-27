<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The learner's personal dashboard, styled after a student portal.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $completedLessonIds = $user->completedLessons()->pluck('lessons.id');

        $courses = $user->enrolledCourses()
            ->with(['instructor', 'lessons:id,course_id'])
            ->latest('enrollments.created_at')
            ->get()
            ->each(function (Course $course) use ($completedLessonIds): void {
                $totalLessons = $course->lessons->count();
                $finishedLessons = $course->lessons->whereIn('id', $completedLessonIds)->count();
                $course->setAttribute('progress', $totalLessons ? (int) round($finishedLessons / $totalLessons * 100) : 0);
            });

        return view('dashboard', [
            'courses' => $courses,
            'stats' => [
                'enrolled' => $courses->count(),
                'completedCourses' => $courses->where('progress', 100)->count(),
                'completedLessons' => $completedLessonIds->count(),
                'averageProgress' => (int) round($courses->avg('progress') ?? 0),
            ],
            'announcements' => Announcement::published()->latest('published_at')->take(5)->get(),
        ]);
    }
}
