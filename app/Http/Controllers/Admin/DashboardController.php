<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $courseQuery = Course::query();
        $enrollmentQuery = Enrollment::query();

        $months = collect(range(5, 0))->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo));
        $recentEnrollments = (clone $enrollmentQuery)
            ->where('created_at', '>=', $months->first())
            ->get(['created_at'])
            ->countBy(fn (Enrollment $enrollment) => $enrollment->created_at->format('m/Y'));

        return view('admin.dashboard', [
            'stats' => [
                'courses' => (clone $courseQuery)->count(),
                'students' => User::where('role', UserRole::Student)->count(),
                'enrollments' => (clone $enrollmentQuery)->count(),
                'reviews' => Review::count(),
                'pending' => Course::where('status', CourseStatus::Pending)->count(),
            ],
            'enrollmentChart' => [
                'labels' => $months->map(fn ($month) => 'T'.$month->format('m/Y'))->all(),
                'values' => $months->map(fn ($month) => $recentEnrollments->get($month->format('m/Y'), 0))->all(),
            ],
            'categoryChart' => Category::withCount('courses')->orderByDesc('courses_count')->take(6)->get(['id', 'name']),
            'latestEnrollments' => (clone $enrollmentQuery)->with(['user', 'course'])->latest()->take(6)->get(),
            'pendingCourses' => Course::where('status', CourseStatus::Pending)->with('instructor')->latest('submitted_at')->take(5)->get(),
            'popularCourses' => (clone $courseQuery)->withCount('enrollments')->orderByDesc('enrollments_count')->take(5)->get(),
        ]);
    }
}
