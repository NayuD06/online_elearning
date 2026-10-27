<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    private function image(string $name): UploadedFile
    {
        // 1x1 PNG, so the tests do not depend on the GD extension.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    public function test_teacher_creates_course_as_draft_with_thumbnail_and_multiple_images(): void
    {
        Storage::fake('media');
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $category = Category::factory()->create();

        $this->actingAs($teacher)->post(route('instructor.courses.store'), [
            'title' => 'Lập trình PHP cơ bản',
            'summary' => 'Khoá học nhập môn PHP',
            'description' => '<p>Nội dung <strong>chi tiết</strong></p>',
            'category_id' => $category->id,
            'price' => 0,
            'level' => 'beginner',
            // Instructors cannot publish on their own.
            'status' => 'published',
            'thumbnail' => $this->image('cover.png'),
            'images' => [$this->image('a.png'), $this->image('b.png'), $this->image('c.png')],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $course = Course::firstOrFail();
        $this->assertSame('lap-trinh-php-co-ban', $course->slug);
        $this->assertTrue($course->instructor->is($teacher));
        $this->assertSame(CourseStatus::Draft, $course->status);
        $this->assertNull($course->published_at);
        $this->assertCount(3, $course->images);
        Storage::disk('media')->assertExists([$course->thumbnail, ...$course->images->pluck('path')]);
    }

    public function test_images_can_be_removed_when_updating(): void
    {
        Storage::fake('media');
        $course = Course::factory()->create();
        $image = $course->images()->create(['path' => $this->image('old.png')->store('courses', 'media'), 'sort_order' => 1]);

        $this->actingAs($course->instructor)->put(route('instructor.courses.update', $course), [
            'title' => 'Tên mới',
            'summary' => $course->summary,
            'price' => 100000,
            'level' => 'advanced',
            'delete_images' => [$image->id],
            'images' => [$this->image('new.png')],
        ])->assertSessionHasNoErrors();

        $course->refresh();
        $this->assertSame('Tên mới', $course->title);
        $this->assertCount(1, $course->images);
        $this->assertFalse($course->images->contains($image));
        Storage::disk('media')->assertMissing($image->path);
    }

    public function test_teacher_can_only_manage_own_courses(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $ownCourse = Course::factory()->create(['instructor_id' => $teacher->id]);
        $otherCourse = Course::factory()->create();

        $this->actingAs($teacher);

        $this->get(route('instructor.courses.index'))->assertSee($ownCourse->title)->assertDontSee($otherCourse->title);
        $this->get(route('instructor.courses.edit', $ownCourse))->assertOk();
        $this->get(route('instructor.courses.edit', $otherCourse))->assertForbidden();
        $this->get(route('instructor.courses.curriculum', $otherCourse))->assertForbidden();
        $this->delete(route('instructor.courses.destroy', $otherCourse))->assertForbidden();
        $this->assertModelExists($otherCourse);
    }

    public function test_admin_and_instructor_areas_are_separate(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($student)->get(route('admin.courses.index'))->assertForbidden();
        $this->get(route('instructor.dashboard'))->assertForbidden();
        $this->post(route('instructor.courses.store'), [])->assertForbidden();

        $this->actingAs($teacher)->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.courses.index'))->assertForbidden();
        $this->get(route('instructor.dashboard'))->assertOk();

        $this->actingAs($admin)->get(route('instructor.dashboard'))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_course_review_flow(): void
    {
        $course = Course::factory()->draft()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // A course needs content before it can be submitted.
        $this->actingAs($course->instructor)->post(route('instructor.courses.submit', $course))->assertSessionHas('error');
        $this->assertSame(CourseStatus::Draft, $course->fresh()->status);

        $section = Section::factory()->create(['course_id' => $course->id]);
        $course->lessons()->create(['section_id' => $section->id, 'title' => 'Bài 1', 'position' => 1]);

        $this->post(route('instructor.courses.submit', $course))->assertSessionHas('success');
        $this->assertSame(CourseStatus::Pending, $course->fresh()->status);
        $this->get(route('courses.show', $course))->assertOk();

        $this->actingAs($admin)->patch(route('admin.courses.reject', $course), [])->assertSessionHasErrors('rejection_reason');
        $this->patch(route('admin.courses.reject', $course), ['rejection_reason' => 'Thiếu đề cương'])->assertSessionHas('success');
        $this->assertSame(CourseStatus::Rejected, $course->fresh()->status);
        $this->assertSame('Thiếu đề cương', $course->fresh()->rejection_reason);

        $this->actingAs($course->instructor)->post(route('instructor.courses.submit', $course));
        $this->assertSame(CourseStatus::Pending, $course->fresh()->status);
        $this->assertNull($course->fresh()->rejection_reason);

        $this->actingAs($admin)->patch(route('admin.courses.approve', $course))->assertSessionHas('success');
        $this->assertSame(CourseStatus::Published, $course->fresh()->status);
        $this->assertNotNull($course->fresh()->published_at);

        auth()->logout();
        $this->get(route('courses.show', $course))->assertOk();

        $this->actingAs($admin)->patch(route('admin.courses.hide', $course))->assertSessionHas('success');
        $this->assertSame(CourseStatus::Hidden, $course->fresh()->status);

        auth()->logout();
        $this->get(route('courses.show', $course))->assertNotFound();
    }

    public function test_rich_text_is_sanitized_on_display(): void
    {
        $course = Course::factory()->create(['description' => '<p onclick="alert(1)">An toàn</p><script>alert(1)</script>']);

        $this->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('An toàn')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('onclick', false);
    }

    public function test_sections_and_lessons_crud(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($course->instructor)->post(route('instructor.courses.sections.store', $course), ['title' => 'Chương 1'])->assertRedirect();
        $section = $course->sections()->firstOrFail();
        $this->assertSame(1, $section->position);

        $this->post(route('instructor.courses.lessons.store', $course), [
            'section_id' => $section->id,
            'title' => 'Bài 1',
            'duration_minutes' => 15,
            'is_preview' => '1',
        ])->assertRedirect(route('instructor.courses.curriculum', $course));

        $lesson = $section->lessons()->firstOrFail();
        $this->assertTrue($lesson->is_preview);
        $this->get(route('instructor.courses.curriculum', $course))->assertOk()->assertSee('Chương 1')->assertSee('Bài 1');

        $this->put(route('instructor.courses.lessons.update', [$course, $lesson]), ['section_id' => $section->id, 'title' => 'Bài 1 (sửa)', 'duration_minutes' => 20])->assertRedirect();
        $this->assertSame('Bài 1 (sửa)', $lesson->fresh()->title);
        $this->assertFalse($lesson->fresh()->is_preview);

        // A lesson cannot be moved into a chapter of another course.
        $foreignSection = Section::factory()->create();
        $this->put(route('instructor.courses.lessons.update', [$course, $lesson]), ['section_id' => $foreignSection->id, 'title' => 'X', 'duration_minutes' => 5])->assertSessionHasErrors('section_id');

        $this->put(route('instructor.courses.sections.update', [$course, $section]), ['title' => 'Chương mở đầu'])->assertRedirect();
        $this->assertSame('Chương mở đầu', $section->fresh()->title);

        $this->delete(route('instructor.courses.sections.destroy', [$course, $section]))->assertRedirect();
        $this->assertModelMissing($section);
        $this->assertModelMissing($lesson);
    }
}
