<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistAndProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_adds_and_removes_a_course_from_the_wishlist(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($student)->post(route('wishlist.toggle', $course))->assertSessionHas('success');
        $this->assertTrue($student->hasInWishlist($course));

        $this->get(route('wishlist.index'))->assertOk()->assertSee($course->title);
        $this->get(route('courses.show', $course))->assertSee('Đã yêu thích');

        $this->post(route('wishlist.toggle', $course));
        $this->assertFalse($student->hasInWishlist($course));
        $this->get(route('wishlist.index'))->assertDontSee($course->title);
    }

    public function test_unpublished_courses_cannot_be_added_to_the_wishlist(): void
    {
        $draft = Course::factory()->draft()->create();

        $this->actingAs(User::factory()->create())->post(route('wishlist.toggle', $draft))->assertNotFound();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('wishlist.index'))->assertRedirect(route('login'));
        $this->post(route('wishlist.toggle', Course::factory()->create()))->assertRedirect(route('login'));
    }

    public function test_instructor_profile_lists_published_courses_only(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher, 'bio' => 'Mười năm dạy lập trình web.']);
        $published = Course::factory()->create(['instructor_id' => $teacher->id]);
        $draft = Course::factory()->draft()->create(['instructor_id' => $teacher->id]);

        $this->get(route('instructors.show', $teacher))
            ->assertOk()
            ->assertSee($teacher->name)
            ->assertSee('Mười năm dạy lập trình web.')
            ->assertSee($published->title)
            ->assertDontSee($draft->title);

        $this->get(route('courses.show', $published))->assertSee(route('instructors.show', $teacher));
    }

    public function test_profile_page_only_exists_for_instructors(): void
    {
        $this->get(route('instructors.show', User::factory()->create()))->assertNotFound();
        $this->get(route('instructors.show', User::factory()->create(['role' => UserRole::Admin])))->assertNotFound();
        $this->get(route('instructors.show', User::factory()->create(['role' => UserRole::Teacher, 'locked_at' => now()])))->assertNotFound();
    }

    public function test_custom_error_pages(): void
    {
        $this->get('/trang-khong-ton-tai')->assertNotFound()->assertSee('Không tìm thấy trang')->assertSee('Về trang chủ');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('Bạn không có quyền truy cập')
            ->assertSee('Bạn không có quyền truy cập trang này.');
    }
}
