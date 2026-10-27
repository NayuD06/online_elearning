<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SectionController extends Controller
{
    /**
     * The course outline: chapters with their lessons and quizzes.
     */
    public function index(Course $course): View
    {
        Gate::authorize('manage', $course);

        return view('instructor.courses.curriculum', [
            'course' => $course,
            'sections' => $course->sections()
                ->with(['lessons', 'quizzes' => fn ($query) => $query->withCount('questions')])
                ->get(),
            'lessonsWithoutSection' => $course->lessons()->whereNull('section_id')->get(),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate($this->rules(), [], $this->attributes());

        $course->sections()->create([
            'title' => $validated['title'],
            'position' => $validated['position'] ?? (int) $course->sections()->max('position') + 1,
        ]);

        return back()->with('success', 'Đã thêm chương mới.');
    }

    public function update(Request $request, Course $course, Section $section): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate($this->rules(), [], $this->attributes());

        $section->update([
            'title' => $validated['title'],
            'position' => $validated['position'] ?? $section->position,
        ]);

        return back()->with('success', 'Đã cập nhật chương.');
    }

    public function destroy(Course $course, Section $section): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $section->delete();

        return back()->with('success', 'Đã xoá chương cùng các bài học và bài kiểm tra bên trong.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributes(): array
    {
        return ['title' => 'tên chương', 'position' => 'thứ tự'];
    }
}
