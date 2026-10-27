<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('attachments');

        $this->course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create(['course_id' => $this->course->id, 'section_id' => $section->id]);
    }

    private function lessonPayload(array $overrides = []): array
    {
        return [
            'section_id' => $this->lesson->section_id,
            'title' => $this->lesson->title,
            'duration_minutes' => 10,
            ...$overrides,
        ];
    }

    private function upload(UploadedFile ...$files)
    {
        return $this->actingAs($this->course->instructor)
            ->put(route('instructor.courses.lessons.update', [$this->course, $this->lesson]), $this->lessonPayload(['attachments' => $files]));
    }

    public function test_instructor_attaches_documents_and_enrolled_students_download_them(): void
    {
        $this->upload(UploadedFile::fake()->create('Slide bài 1.pdf', 300, 'application/pdf'))
            ->assertRedirect(route('instructor.courses.curriculum', $this->course));

        $attachment = $this->lesson->attachments()->firstOrFail();
        $this->assertSame('Slide bài 1.pdf', $attachment->name);
        $this->assertStringEndsWith('.pdf', $attachment->path);
        Storage::disk('attachments')->assertExists($attachment->path);

        $student = User::factory()->create();
        $student->enrollments()->create(['course_id' => $this->course->id]);

        $this->actingAs($student)->get(route('learn.show', [$this->course, $this->lesson]))->assertSee('Slide bài 1.pdf');

        $response = $this->get(route('attachments.download', [$this->course, $this->lesson, $attachment]));
        $response->assertOk()->assertDownload();
        // The original (Vietnamese) name is kept, with an ASCII fallback for old browsers.
        $this->assertStringContainsString("filename*=utf-8''".rawurlencode('Slide bài 1.pdf'), $response->headers->get('Content-Disposition'));
    }

    public function test_students_who_are_not_enrolled_cannot_download(): void
    {
        $this->upload(UploadedFile::fake()->create('de-cuong.pdf', 10, 'application/pdf'));
        $attachment = $this->lesson->attachments()->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get(route('attachments.download', [$this->course, $this->lesson, $attachment]))
            ->assertForbidden();

        $this->post('/logout');
        $this->get(route('attachments.download', [$this->course, $this->lesson, $attachment]))->assertRedirect(route('login'));
    }

    public function test_attachment_must_belong_to_the_lesson_in_the_url(): void
    {
        $this->upload(UploadedFile::fake()->create('de-cuong.pdf', 10, 'application/pdf'));
        $attachment = $this->lesson->attachments()->firstOrFail();

        // Enrolled in another course: cannot reach this course's file through it.
        $otherCourse = Course::factory()->create();
        $otherLesson = Lesson::factory()->create(['course_id' => $otherCourse->id]);
        $student = User::factory()->create();
        $student->enrollments()->create(['course_id' => $otherCourse->id]);

        $this->actingAs($student)
            ->get(route('attachments.download', [$otherCourse, $otherLesson, $attachment]))
            ->assertNotFound();
    }

    public function test_dangerous_or_oversized_files_are_rejected(): void
    {
        $this->upload(UploadedFile::fake()->create('shell.php', 1, 'application/x-php'))->assertSessionHasErrors('attachments.0');
        $this->upload(UploadedFile::fake()->create('virus.exe', 1, 'application/x-msdownload'))->assertSessionHasErrors('attachments.0');
        $this->upload(UploadedFile::fake()->create('qua-lon.pdf', LessonAttachment::MAX_KILOBYTES + 1, 'application/pdf'))->assertSessionHasErrors('attachments.0');

        $this->assertSame(0, LessonAttachment::count());
    }

    public function test_instructor_deletes_an_attachment_and_its_file(): void
    {
        $this->upload(UploadedFile::fake()->create('de-cuong.pdf', 10, 'application/pdf'));
        $attachment = $this->lesson->attachments()->firstOrFail();

        $this->actingAs(User::factory()->create(['role' => 'teacher']))
            ->delete(route('instructor.courses.lessons.attachments.destroy', [$this->course, $this->lesson, $attachment]))
            ->assertForbidden();

        $this->actingAs($this->course->instructor)
            ->delete(route('instructor.courses.lessons.attachments.destroy', [$this->course, $this->lesson, $attachment]))
            ->assertSessionHas('success');

        $this->assertModelMissing($attachment);
        Storage::disk('attachments')->assertMissing($attachment->path);
    }

    public function test_deleting_a_lesson_removes_its_files(): void
    {
        $this->upload(UploadedFile::fake()->create('de-cuong.pdf', 10, 'application/pdf'));
        $path = $this->lesson->attachments()->value('path');

        $this->actingAs($this->course->instructor)->delete(route('instructor.courses.lessons.destroy', [$this->course, $this->lesson]));

        Storage::disk('attachments')->assertMissing($path);
    }
}
