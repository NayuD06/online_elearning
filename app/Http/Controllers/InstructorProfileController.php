<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\View\View;

class InstructorProfileController extends Controller
{
    /**
     * Public page of an instructor: bio, teaching stats and published courses.
     */
    public function show(User $instructor): View
    {
        abort_unless($instructor->role === UserRole::Teacher && ! $instructor->isLocked(), 404);

        $courses = $instructor->taughtCourses()
            ->published()
            ->with('category')
            ->withCount(['lessons', 'enrollments'])
            ->withAvg(['reviews' => fn ($query) => $query->where('is_visible', true)], 'rating')
            ->latest('published_at')
            ->get();

        $courseIds = $courses->pluck('id');
        $reviews = Review::whereIn('course_id', $courseIds)->where('is_visible', true);

        return view('instructors.show', [
            'instructor' => $instructor,
            'courses' => $courses,
            'stats' => [
                'courses' => $courses->count(),
                'students' => Enrollment::whereIn('course_id', $courseIds)->distinct()->count('user_id'),
                'reviews' => (clone $reviews)->count(),
                'rating' => round((float) (clone $reviews)->avg('rating'), 1),
            ],
        ]);
    }
}
