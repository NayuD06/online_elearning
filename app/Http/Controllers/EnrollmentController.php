<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->isPublished(), 404);

        $request->user()->enrollments()->firstOrCreate(['course_id' => $course->id]);

        return to_route('learn.show', $course)->with('success', 'Ghi danh thành công! Chúc bạn học tốt.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $request->user()->enrollments()->where('course_id', $course->id)->delete();

        return to_route('courses.show', $course)->with('success', 'Bạn đã huỷ ghi danh khoá học.');
    }
}
