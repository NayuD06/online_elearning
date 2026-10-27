<?php

namespace App\Http\Controllers\Instructor;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\CourseRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\User;
use App\Notifications\CourseSubmittedForReview;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = $request->user()->taughtCourses()
            ->with('category')
            ->withCount(['lessons', 'enrollments', 'sections', 'quizzes'])
            ->filter($request->only('q'))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('instructor.courses.index', [
            'courses' => $courses,
            'statuses' => CourseStatus::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Course::class);

        return view('instructor.courses.form', [
            'course' => new Course(['level' => CourseLevel::Beginner, 'status' => CourseStatus::Draft, 'price' => 0]),
            ...$this->formOptions(),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Course::class);

        $course = DB::transaction(function () use ($request): Course {
            $course = Course::create([
                ...$request->safe()->only(['title', 'category_id', 'summary', 'description', 'price', 'level']),
                'instructor_id' => $request->user()->id,
                'status' => CourseStatus::Draft,
                'slug' => UniqueSlug::for(Course::class, $request->string('title')),
                'thumbnail' => $request->file('thumbnail')?->store('courses/thumbnails', 'media'),
            ]);

            $this->storeGalleryImages($request, $course);

            return $course;
        });

        return to_route('instructor.courses.curriculum', $course)->with('success', 'Đã tạo khoá học (bản nháp). Hãy thêm chương, bài học rồi gửi duyệt.');
    }

    public function edit(Course $course): View
    {
        Gate::authorize('manage', $course);

        $course->load('images')->loadCount(['lessons', 'sections']);

        return view('instructor.courses.form', [
            'course' => $course,
            ...$this->formOptions(),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $changed = DB::transaction(function () use ($request, $course): bool {
            $attributes = [
                ...$request->safe()->only(['title', 'category_id', 'summary', 'description', 'price', 'level']),
                'slug' => UniqueSlug::for(Course::class, $request->string('title'), $course->id),
            ];

            if ($request->hasFile('thumbnail')) {
                if ($course->thumbnail) {
                    Storage::disk('media')->delete($course->thumbnail);
                }

                $attributes['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'media');
            }

            $course->update($attributes);

            $imagesToDelete = $course->images()->whereIn('id', $request->input('delete_images', []))->get();
            Storage::disk('media')->delete($imagesToDelete->pluck('path')->all());
            $course->images()->whereKey($imagesToDelete->modelKeys())->delete();

            $this->storeGalleryImages($request, $course);

            return $course->wasChanged() || $imagesToDelete->isNotEmpty() || $request->hasFile('images');
        });

        $message = $changed && $course->sendBackForReview() ? Course::RESUBMITTED_MESSAGE : 'Đã lưu thay đổi khoá học.';

        return to_route('instructor.courses.edit', $course)->with('success', $message);
    }

    /**
     * Send a draft (or a rejected course after fixing it) to the admins for review.
     */
    public function submit(Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        if (! $course->status->canBeSubmitted()) {
            return back()->with('error', 'Chỉ khoá học ở trạng thái bản nháp hoặc bị từ chối mới gửi duyệt được.');
        }

        if (! $course->lessons()->exists()) {
            return back()->with('error', 'Khoá học cần ít nhất một bài học trước khi gửi duyệt.');
        }

        $course->update([
            'status' => CourseStatus::Pending,
            'rejection_reason' => null,
            'submitted_at' => now(),
        ]);

        User::where('role', UserRole::Admin)->get()->each->notify(new CourseSubmittedForReview($course));

        return back()->with('success', 'Đã gửi khoá học cho quản trị viên duyệt.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        Storage::disk('media')->delete(array_filter([$course->thumbnail, ...$course->images->pluck('path')]));
        LessonAttachment::deleteFiles(LessonAttachment::whereIn('lesson_id', $course->lessons()->select('id'))->get());
        Lesson::deleteVideos($course->lessons()->whereNotNull('video_path')->get(['id', 'video_path']));
        $course->delete();

        return to_route('instructor.courses.index')->with('success', 'Đã xoá khoá học.');
    }

    private function storeGalleryImages(CourseRequest $request, Course $course): void
    {
        $nextSortOrder = (int) $course->images()->max('sort_order') + 1;

        foreach ($request->file('images', []) as $image) {
            $course->images()->create([
                'path' => $image->store("courses/{$course->id}/gallery", 'media'),
                'sort_order' => $nextSortOrder++,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'levels' => CourseLevel::cases(),
        ];
    }
}
