<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Notifications\NewStudentEnrolled;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->isPublished(), 404);

        $user = $request->user();

        // Paid courses are joined by paying (the instructor and admins may still open them for free).
        if (! $course->isFree() && ! $user->can('manage', $course) && ! $user->hasPaidFor($course)) {
            return to_route('payments.checkout', $course);
        }

        $enrollment = $user->enrollments()->firstOrCreate(['course_id' => $course->id]);

        if ($enrollment->wasRecentlyCreated && $course->instructor?->isTeacher() && $course->instructor_id !== $user->id) {
            $course->instructor->notify(new NewStudentEnrolled($course, $user));
        }

        return to_route('learn.show', $course)->with('success', 'Ghi danh thành công! Chúc bạn học tốt.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        if ($request->user()->hasPaidFor($course)) {
            return back()->with('error', 'Khoá học đã thanh toán không tự huỷ ghi danh được. Liên hệ quản trị viên nếu cần hỗ trợ hoàn tiền.');
        }

        $request->user()->enrollments()->where('course_id', $course->id)->delete();

        return to_route('courses.show', $course)->with('success', 'Bạn đã huỷ ghi danh khoá học.');
    }
}
