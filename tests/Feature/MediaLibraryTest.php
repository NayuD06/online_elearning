<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_stored_images_and_where_they_are_used(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('avatars/me.png', 'png');
        Storage::disk('media')->put('courses/thumbnails/cover.jpg', 'jpg');
        Storage::disk('media')->put('editor/2026/10/inline.png', 'png');
        Storage::disk('media')->put('announcements/old.png', 'png');
        Storage::disk('media')->put('notes.txt', 'not an image');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->create(['name' => 'Học viên A', 'avatar' => 'avatars/me.png']);
        $course = Course::factory()->create(['title' => 'Lập trình PHP', 'thumbnail' => 'courses/thumbnails/cover.jpg']);
        Lesson::factory()->for($course)->create([
            'title' => 'Bài mở đầu',
            'content' => '<p><img src="'.Storage::disk('media')->url('editor/2026/10/inline.png').'"></p>',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.media.index'))->assertOk();

        $response->assertSee('avatars/me.png')
            ->assertSee('Ảnh đại diện của Học viên A')
            ->assertSee('Ảnh bìa khoá &quot;Lập trình PHP&quot;', false)
            ->assertSee('Trong bài học &quot;Bài mở đầu&quot;', false)
            ->assertSee('announcements/old.png')
            ->assertSee('Không còn dùng')
            ->assertDontSee('notes.txt')
            ->assertSee('storage/app/public');

        $this->get(route('admin.media.index', ['unused' => 1]))
            ->assertSee('announcements/old.png')
            ->assertDontSee('avatars/me.png');

        $this->get(route('admin.media.index', ['folder' => 'courses']))
            ->assertSee('courses/thumbnails/cover.jpg')
            ->assertDontSee('announcements/old.png');
    }

    public function test_announcement_image_links_to_its_edit_page(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('announcements/banner.png', 'png');
        $announcement = Announcement::factory()->create(['image' => 'announcements/banner.png']);

        $this->actingAs($announcement->author)
            ->get(route('admin.media.index'))
            ->assertSee(route('admin.announcements.edit', $announcement));
    }

    public function test_only_admins_can_open_the_media_library(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($teacher)->get(route('admin.media.index'))->assertForbidden();
    }

    public function test_cloudinary_without_a_valid_url_falls_back_to_local_storage(): void
    {
        $this->assertSame('local', config('filesystems.disks.media.driver'));
    }
}
