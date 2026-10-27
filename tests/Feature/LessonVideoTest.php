<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filesystem\CloudinaryAdapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Mockery;
use Tests\TestCase;

class LessonVideoTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');

        $this->course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create(['course_id' => $this->course->id, 'section_id' => $section->id]);
    }

    private function video(string $name = 'bai-1.mp4', int $kilobytes = 500, string $mimeType = 'video/mp4'): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kilobytes, $mimeType);
    }

    private function saveLesson(array $data)
    {
        return $this->actingAs($this->course->instructor)
            ->put(route('instructor.courses.lessons.update', [$this->course, $this->lesson]), [
                'section_id' => $this->lesson->section_id,
                'title' => $this->lesson->title,
                'duration_minutes' => 10,
                ...$data,
            ]);
    }

    public function test_instructor_uploads_a_video_that_enrolled_students_watch(): void
    {
        $this->saveLesson(['video' => $this->video()])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('instructor.courses.curriculum', $this->course));

        $path = $this->lesson->refresh()->video_path;
        $this->assertStringStartsWith('lesson-videos/', $path);
        $this->assertStringEndsWith('.mp4', $path);
        Storage::disk('media')->assertExists($path);

        $student = User::factory()->create();
        $student->enrollments()->create(['course_id' => $this->course->id]);

        $this->actingAs($student)->get(route('learn.show', [$this->course, $this->lesson]))
            ->assertOk()
            ->assertSee('<video src="'.Storage::disk('media')->url($path).'"', false);

        $this->actingAs($this->course->instructor)->get(route('instructor.courses.curriculum', $this->course))
            ->assertSee('Bài học video');
        $this->get(route('instructor.courses.lessons.edit', [$this->course, $this->lesson]))
            ->assertOk()
            ->assertSee('Xoá video đã tải lên');
    }

    public function test_new_lesson_can_be_created_with_a_video(): void
    {
        $this->actingAs($this->course->instructor)->post(route('instructor.courses.lessons.store', $this->course), [
            'section_id' => $this->lesson->section_id,
            'title' => 'Bài có video',
            'duration_minutes' => 12,
            'video' => $this->video('gioi-thieu.webm', 300, 'video/webm'),
        ])->assertSessionHasNoErrors();

        $lesson = Lesson::where('title', 'Bài có video')->firstOrFail();
        $this->assertStringEndsWith('.webm', $lesson->video_path);
        Storage::disk('media')->assertExists($lesson->video_path);
    }

    public function test_uploaded_video_is_shown_before_the_youtube_link(): void
    {
        $this->saveLesson([
            'video' => $this->video(),
            'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->course->instructor)->get(route('learn.show', [$this->course, $this->lesson]))
            ->assertSee('<video src=', false)
            ->assertDontSee('youtube.com/embed', false);
    }

    public function test_a_new_video_replaces_the_old_file(): void
    {
        $this->saveLesson(['video' => $this->video('cu.mp4')]);
        $oldPath = $this->lesson->refresh()->video_path;

        $this->saveLesson(['video' => $this->video('moi.mp4')])->assertSessionHasNoErrors();

        $newPath = $this->lesson->refresh()->video_path;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('media')->assertMissing($oldPath);
        Storage::disk('media')->assertExists($newPath);
    }

    public function test_saving_without_a_file_keeps_the_video_and_remove_deletes_it(): void
    {
        $this->saveLesson(['video' => $this->video()]);
        $path = $this->lesson->refresh()->video_path;

        $this->saveLesson(['title' => 'Đổi tên bài'])->assertSessionHasNoErrors();
        $this->assertSame($path, $this->lesson->refresh()->video_path);

        $this->saveLesson(['remove_video' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($this->lesson->refresh()->video_path);
        Storage::disk('media')->assertMissing($path);
    }

    public function test_only_mp4_or_webm_videos_within_the_size_limit_are_accepted(): void
    {
        $this->saveLesson(['video' => UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream')])
            ->assertSessionHasErrors('video');
        $this->saveLesson(['video' => UploadedFile::fake()->create('slide.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('video');
        // A renamed file whose content is not a video.
        $this->saveLesson(['video' => UploadedFile::fake()->create('trick.mp4', 100, 'text/html')])
            ->assertSessionHasErrors('video');
        $this->saveLesson(['video' => $this->video('qua-lon.mp4', Lesson::MAX_VIDEO_KILOBYTES + 1)])
            ->assertSessionHasErrors('video');

        $this->assertNull($this->lesson->refresh()->video_path);
        $this->assertEmpty(Storage::disk('media')->allFiles());
    }

    public function test_youtube_field_only_accepts_web_addresses(): void
    {
        $this->saveLesson(['video_url' => 'javascript:alert(1)'])->assertSessionHasErrors('video_url');
    }

    public function test_other_instructors_cannot_upload_videos(): void
    {
        $otherTeacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($otherTeacher)
            ->put(route('instructor.courses.lessons.update', [$this->course, $this->lesson]), [
                'section_id' => $this->lesson->section_id,
                'title' => 'Chiếm bài',
                'duration_minutes' => 10,
                'video' => $this->video(),
            ])
            ->assertForbidden();

        $this->assertEmpty(Storage::disk('media')->allFiles());
    }

    public function test_students_who_are_not_enrolled_do_not_get_the_video(): void
    {
        $this->saveLesson(['video' => $this->video()]);

        $this->actingAs(User::factory()->create())
            ->get(route('learn.show', [$this->course, $this->lesson]))
            ->assertForbidden();
    }

    public function test_deleting_the_lesson_or_course_deletes_the_video(): void
    {
        $this->saveLesson(['video' => $this->video()]);
        $path = $this->lesson->refresh()->video_path;

        $this->actingAs($this->course->instructor)
            ->delete(route('instructor.courses.lessons.destroy', [$this->course, $this->lesson]));
        Storage::disk('media')->assertMissing($path);

        $lesson = Lesson::factory()->create(['course_id' => $this->course->id, 'section_id' => $this->lesson->section_id]);
        $lesson->replaceVideo($this->video());

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->delete(route('admin.courses.destroy', $this->course));
        $this->assertModelMissing($this->course);
        Storage::disk('media')->assertMissing($lesson->video_path);
    }

    public function test_on_cloudinary_the_video_is_uploaded_as_a_video_asset(): void
    {
        $uploadApi = Mockery::mock(UploadApi::class);
        $uploadApi->shouldReceive('upload')->once()->withArgs(fn ($file, array $options) => $options['resource_type'] === 'video'
            && str_starts_with($options['public_id'], 'online-elearning/lesson-videos/')
            && ! str_ends_with($options['public_id'], '.mp4'));

        $cloudinary = Mockery::mock(Cloudinary::class, ['cloudinary://key:secret@demo-cloud'])->makePartial();
        $cloudinary->shouldReceive('uploadApi')->andReturn($uploadApi);
        $adapter = new CloudinaryAdapter($cloudinary, 'online-elearning');
        Storage::set('media', new FilesystemAdapter(new Filesystem($adapter), $adapter, ['throw' => true]));

        $this->saveLesson(['video' => $this->video()])->assertSessionHasNoErrors();

        $this->assertMatchesRegularExpression(
            '~^https://res\.cloudinary\.com/demo-cloud/video/upload/online-elearning/lesson-videos/\w{40}\.mp4$~',
            $this->lesson->refresh()->video_file_url,
        );
    }
}
