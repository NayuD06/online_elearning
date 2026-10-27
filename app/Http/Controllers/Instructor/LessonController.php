<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function create(Request $request, Course $course): View
    {
        Gate::authorize('manage', $course);

        $sectionId = $request->integer('section') ?: $course->sections()->value('id');

        return view('instructor.lessons.form', [
            'course' => $course,
            'sections' => $course->sections()->get(['id', 'title']),
            'lesson' => new Lesson([
                'section_id' => $sectionId,
                'position' => (int) $course->lessons()->where('section_id', $sectionId)->max('position') + 1,
                'duration_minutes' => 10,
            ]),
        ]);
    }

    public function store(LessonRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $course->lessons()->create([
            ...$request->validated(),
            'position' => $request->integer('position') ?: (int) $course->lessons()->where('section_id', $request->integer('section_id'))->max('position') + 1,
        ]);

        return to_route('instructor.courses.curriculum', $course)->with('success', 'Đã thêm bài học.');
    }

    public function edit(Course $course, Lesson $lesson): View
    {
        Gate::authorize('manage', $course);

        return view('instructor.lessons.form', [
            'course' => $course,
            'sections' => $course->sections()->get(['id', 'title']),
            'lesson' => $lesson,
        ]);
    }

    public function update(LessonRequest $request, Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $lesson->update([
            ...$request->validated(),
            'position' => $request->integer('position', $lesson->position),
        ]);

        return to_route('instructor.courses.curriculum', $course)->with('success', 'Đã cập nhật bài học.');
    }

    public function destroy(Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $lesson->delete();

        return back()->with('success', 'Đã xoá bài học.');
    }
}
