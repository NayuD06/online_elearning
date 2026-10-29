<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use App\Notifications\CertificateIssued;
use Illuminate\Http\JsonResponse;
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
            'attachments' => $lesson->attachments()->get(),
            'questions' => $lesson->questions()->with(['user', 'answers.user'])->latest()->paginate(10)->fragment('hoi-dap'),
            'canAnswer' => $request->user()->can('manage', $course),
            'certificate' => $request->user()->certificates()->where('course_id', $course->id)->first(),
        ]);
    }

    /**
     * Toggle the "finished" mark of a lesson for the current learner.
     *
     * The learning page sends this with fetch and gets JSON back so it can update in place;
     * a plain form post (no JavaScript) still gets the usual redirect.
     */
    public function complete(Request $request, Course $course, Lesson $lesson): JsonResponse|RedirectResponse
    {
        Gate::authorize('learn', $course);
        abort_unless($lesson->course_id === $course->id, 404);

        $user = $request->user();
        $user->completedLessons()->toggle($lesson->id);
        $isCompleted = $user->completedLessons()->whereKey($lesson->id)->exists();

        $progress = $user->progressIn($course);
        $isCourseFinished = $progress === 100;
        $user->enrollments()->where('course_id', $course->id)->update(['completed_at' => $isCourseFinished ? now() : null]);

        // A certificate stays valid even if a lesson is unmarked later.
        $newCertificate = null;

        if ($isCourseFinished && $user->isEnrolledIn($course)) {
            $certificate = Certificate::issueFor($user, $course);

            if ($certificate->wasRecentlyCreated) {
                $user->notify(new CertificateIssued($certificate));
                $newCertificate = $certificate;
            }
        }

        $lessons = $course->curriculumLessons()->get();
        $nextLesson = $isCompleted ? $lessons->get($lessons->search(fn (Lesson $item) => $item->id === $lesson->id) + 1) : null;

        $message = match (true) {
            $newCertificate !== null => 'Chúc mừng! Bạn đã hoàn thành khoá học và nhận được chứng chỉ.',
            $isCourseFinished => 'Chúc mừng! Bạn đã hoàn thành khoá học.',
            $nextLesson !== null => 'Đã hoàn thành bài học. Tiếp tục bài kế tiếp nhé!',
            $isCompleted => 'Đã hoàn thành bài học.',
            default => 'Đã bỏ đánh dấu hoàn thành.',
        };

        if ($request->wantsJson()) {
            // The page leaves for the certificate, which shows this message as usual.
            if ($newCertificate) {
                $request->session()->flash('success', $message);
            }

            return response()->json([
                'completed' => $isCompleted,
                'progress' => $progress,
                'course_finished' => $isCourseFinished,
                'message' => $message,
                'next_url' => $nextLesson ? route('learn.show', [$course, $nextLesson]) : null,
                'certificate_url' => $newCertificate ? route('certificates.show', $newCertificate) : null,
            ]);
        }

        if ($newCertificate) {
            return to_route('certificates.show', $newCertificate)->with('success', $message);
        }

        if ($nextLesson) {
            return to_route('learn.show', [$course, $nextLesson])->with('success', $message);
        }

        return back()->with('success', $message);
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
