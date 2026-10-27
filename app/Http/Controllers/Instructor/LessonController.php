<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
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

        $lesson = $course->lessons()->create([
            ...$request->safe()->except(['attachments', 'video', 'remove_video']),
            'position' => $request->integer('position') ?: (int) $course->lessons()->where('section_id', $request->integer('section_id'))->max('position') + 1,
        ]);

        $this->storeVideo($request, $lesson);
        $this->storeAttachments($request, $lesson);

        return to_route('instructor.courses.curriculum', $course)->with('success', $this->savedMessage($course, 'Đã thêm bài học.'));
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
            ...$request->safe()->except(['attachments', 'video', 'remove_video']),
            'position' => $request->integer('position', $lesson->position),
        ]);

        $this->storeVideo($request, $lesson);
        $this->storeAttachments($request, $lesson);

        $message = $lesson->wasChanged() || $request->hasFile('attachments')
            ? $this->savedMessage($course, 'Đã cập nhật bài học.')
            : 'Đã cập nhật bài học.';

        return to_route('instructor.courses.curriculum', $course)->with('success', $message);
    }

    public function destroy(Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('manage', $course);

        LessonAttachment::deleteFiles($lesson->attachments);
        Lesson::deleteVideos([$lesson]);
        $lesson->delete();

        return back()->with('success', $this->savedMessage($course, 'Đã xoá bài học.'));
    }

    public function destroyAttachment(Course $course, Lesson $lesson, LessonAttachment $attachment): RedirectResponse
    {
        Gate::authorize('manage', $course);

        LessonAttachment::deleteFiles([$attachment]);
        $attachment->delete();

        return back()->with('success', $this->savedMessage($course, "Đã xoá tài liệu \"{$attachment->name}\"."));
    }

    /**
     * The success message, telling the instructor when the change sent an approved course back for review.
     */
    private function savedMessage(Course $course, string $message): string
    {
        return $course->sendBackForReview() ? Course::RESUBMITTED_MESSAGE : $message;
    }

    /**
     * A new file replaces the current video; ticking "remove" without a file drops it.
     */
    private function storeVideo(LessonRequest $request, Lesson $lesson): void
    {
        if ($request->hasFile('video')) {
            $lesson->replaceVideo($request->file('video'));
        } elseif ($request->boolean('remove_video')) {
            $lesson->removeVideo();
        }
    }

    private function storeAttachments(LessonRequest $request, Lesson $lesson): void
    {
        foreach ($request->file('attachments', []) as $file) {
            LessonAttachment::storeFor($lesson, $file);
        }
    }
}
