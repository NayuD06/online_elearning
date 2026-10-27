<?php

namespace App\Http\Controllers;

use App\Enums\CourseLevel;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->string('sort', 'newest')->toString();

        $courses = Course::published()
            ->with(['category', 'instructor'])
            ->withCount(['lessons', 'enrollments'])
            ->withAvg(['reviews' => fn ($query) => $query->where('is_visible', true)], 'rating')
            ->filter($request->only(['q', 'category', 'level']))
            ->when($request->string('price')->toString() === 'free', fn ($query) => $query->where('price', 0))
            ->when($request->string('price')->toString() === 'paid', fn ($query) => $query->where('price', '>', 0))
            ->when($sort === 'popular', fn ($query) => $query->orderByDesc('enrollments_count'))
            ->when($sort === 'price_asc', fn ($query) => $query->orderBy('price'))
            ->when($sort === 'price_desc', fn ($query) => $query->orderByDesc('price'))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('courses.index', [
            'courses' => $courses,
            'categories' => Category::orderBy('name')->get(),
            'levels' => CourseLevel::cases(),
        ]);
    }

    public function show(Request $request, Course $course): View
    {
        $user = $request->user();

        abort_unless($course->isPublished() || $user?->can('manage', $course), 404);

        $course->load(['category', 'instructor', 'images', 'lessons', 'sections.lessons', 'sections.quizzes'])
            ->loadCount('enrollments')
            ->loadAvg(['reviews' => fn ($query) => $query->where('is_visible', true)], 'rating');

        return view('courses.show', [
            'course' => $course,
            'reviews' => $course->reviews()->with('user')->where('is_visible', true)->latest()->paginate(5),
            'isEnrolled' => $user?->isEnrolledIn($course) ?? false,
            'userReview' => $user ? $course->reviews()->where('user_id', $user->id)->first() : null,
            'relatedCourses' => Course::published()
                ->where('category_id', $course->category_id)
                ->whereKeyNot($course->id)
                ->with('category')
                ->withCount(['lessons', 'enrollments'])
                ->withAvg(['reviews' => fn ($query) => $query->where('is_visible', true)], 'rating')
                ->take(3)
                ->get(),
        ]);
    }
}
