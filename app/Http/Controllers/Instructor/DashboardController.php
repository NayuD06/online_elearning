<?php

namespace App\Http\Controllers\Instructor;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $ownCourses = fn (Builder $query) => $query->where('instructor_id', $user->id);

        $enrollmentQuery = Enrollment::query()->whereHas('course', $ownCourses);

        $months = collect(range(5, 0))->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo));
        $recentEnrollments = (clone $enrollmentQuery)
            ->where('created_at', '>=', $months->first())
            ->get(['created_at'])
            ->countBy(fn (Enrollment $enrollment) => $enrollment->created_at->format('m/Y'));

        $visibleReviews = Review::whereHas('course', $ownCourses)->where('is_visible', true);

        return view('instructor.dashboard', [
            'stats' => [
                'courses' => $user->taughtCourses()->count(),
                'students' => (clone $enrollmentQuery)->distinct()->count('user_id'),
                'averageRating' => round((float) (clone $visibleReviews)->avg('rating'), 1),
                'pending' => $user->taughtCourses()->where('status', CourseStatus::Pending)->count(),
            ],
            'enrollmentChart' => [
                'labels' => $months->map(fn ($month) => 'T'.$month->format('m/Y'))->all(),
                'values' => $months->map(fn ($month) => $recentEnrollments->get($month->format('m/Y'), 0))->all(),
            ],
            'courses' => $user->taughtCourses()
                ->withCount('enrollments')
                ->withAvg(['reviews' => fn ($query) => $query->where('is_visible', true)], 'rating')
                ->latest()
                ->take(8)
                ->get(),
            'latestEnrollments' => (clone $enrollmentQuery)->with(['user', 'course'])->latest()->take(6)->get(),
        ]);
    }
}
