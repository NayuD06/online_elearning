<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Create or update the learner's review of a course.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($request->user()->isEnrolledIn($course), 403, 'Bạn cần ghi danh khoá học trước khi đánh giá.');

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [], ['rating' => 'số sao', 'comment' => 'nhận xét']);

        $course->reviews()->updateOrCreate(['user_id' => $request->user()->id], $validated);

        return to_route('courses.show', $course)->withFragment('reviews')->with('success', 'Cảm ơn bạn đã đánh giá khoá học!');
    }
}
