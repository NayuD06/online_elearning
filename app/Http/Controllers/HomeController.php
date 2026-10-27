<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'featuredCourses' => Course::published()
                ->with(['category', 'instructor'])
                ->withCount(['lessons', 'enrollments'])
                ->withAvg(['reviews' => fn ($query) => $query->where('is_visible', true)], 'rating')
                ->orderByDesc('enrollments_count')
                ->take(8)
                ->get(),
            'categories' => Category::withCount(['courses' => fn ($query) => $query->published()])->orderByDesc('courses_count')->take(8)->get(),
            'announcements' => Announcement::published()->latest('published_at')->take(4)->get(),
            'stats' => [
                'courses' => Course::published()->count(),
                'students' => User::where('role', UserRole::Student)->count(),
                'teachers' => User::where('role', UserRole::Teacher)->count(),
                'enrollments' => Enrollment::count(),
            ],
        ]);
    }
}
