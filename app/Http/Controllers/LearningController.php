<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearningController extends Controller
{
    public function show(Request $request, Course $course, ?Lesson $lesson = null): View|RedirectResponse
    {
        $lessons = $course->curriculumLessons()->get();
        $lesson ??= $lessons->first();

        if (! $lesson) {
            return to_route('courses.show', $course)->with('error', 'Khoá học này chưa có bài học nào.');
        }

        abort_unless($lesson->course_id === $course->id, 404);

        // Preview lessons are open to every signed-in user; the rest need enrollment.
        $canLearn = Gate::allows('learn', $course);
        abort_unless($canLearn || $lesson->is_preview, 403, 'Bạn cần ghi danh khoá học để xem bài học này.');

        $completedLessonIds = $request->user()->completedLessons()->where('course_id', $course->id)->pluck('lessons.id');
        $currentIndex = $lessons->search(fn (Lesson $item) => $item->id === $lesson->id);

        return view('learn.show', [
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'outline' => $this->outline($course, $lessons),
            'canLearn' => $canLearn,
            'completedLessonIds' => $completedLessonIds,
            'previousLesson' => $lessons->get($currentIndex - 1),
            'nextLesson' => $lessons->get($currentIndex + 1),
            'progress' => (int) round($completedLessonIds->count() / $lessons->count() * 100),
        ]);
    }

    /**
     * Toggle the "finished" mark of a lesson for the current learner.
     */
    public function complete(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('learn', $course);
        abort_unless($lesson->course_id === $course->id, 404);

        $user = $request->user();
        $user->completedLessons()->toggle($lesson->id);

        $isCourseFinished = $user->progressIn($course) === 100;
        $user->enrollments()->where('course_id', $course->id)->update(['completed_at' => $isCourseFinished ? now() : null]);

        $lessons = $course->curriculumLessons()->get();
        $nextLesson = $lessons->get($lessons->search(fn (Lesson $item) => $item->id === $lesson->id) + 1);

        if ($user->completedLessons()->whereKey($lesson->id)->exists() && $nextLesson) {
            return to_route('learn.show', [$course, $nextLesson])->with('success', 'Đã hoàn thành bài học. Tiếp tục bài kế tiếp nhé!');
        }

        return back()->with('success', $isCourseFinished ? 'Chúc mừng! Bạn đã hoàn thành khoá học.' : 'Đã cập nhật tiến độ.');
    }

    /**
     * Group the lessons by chapter, each chapter ending with its quizzes.
     *
     * @param  Collection<int, Lesson>  $lessons
     * @return Collection<int, array{title: ?string, lessons: Collection<int, Lesson>, quizzes: Collection<int, Quiz>}>
     */
    private function outline(Course $course, Collection $lessons): Collection
    {
        return $course->sections()->with('quizzes')->get()
            ->map(fn (Section $section) => [
                'title' => $section->title,
                'lessons' => $lessons->where('section_id', $section->id),
                'quizzes' => $section->quizzes,
            ])
            ->prepend(['title' => null, 'lessons' => $lessons->whereNull('section_id'), 'quizzes' => collect()])
            ->filter(fn (array $group) => $group['lessons']->isNotEmpty() || $group['quizzes']->isNotEmpty())
            ->values();
    }
}
