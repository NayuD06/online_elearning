<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function signInWithGoogle(string $email): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn((new SocialiteUser)->setRaw([])->map([
            'id' => 'google-owner',
            'name' => 'Chủ email',
            'email' => $email,
            'avatar' => null,
        ]));

        $this->get('/auth/google/callback?code=abc&state=xyz');
    }

    public function test_google_sign_in_takes_back_an_account_registered_by_someone_else(): void
    {
        config(['auth.verify_email' => false]);
        Notification::fake();

        // Someone registers the owner's email with their own password and asks to teach.
        $this->post('/register', [
            'name' => 'Kẻ giả mạo',
            'email' => 'chu.email@gmail.com',
            'password' => 'GiaMao@123',
            'password_confirmation' => 'GiaMao@123',
            'account_type' => 'teacher',
        ]);
        Auth::logout();

        $this->signInWithGoogle('chu.email@gmail.com');

        $user = User::where('email', 'chu.email@gmail.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->hasPassword());
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->hasPendingInstructorRequest());

        Auth::logout();
        $this->assertFalse(Auth::attempt(['email' => 'chu.email@gmail.com', 'password' => 'GiaMao@123']));
    }

    public function test_google_sign_in_ends_the_other_sessions_of_an_unproven_account(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->unverified()->create(['email' => 'chu.email@gmail.com']);
        DB::table('sessions')->insert([
            'id' => 'phien-cua-ke-gia-mao',
            'user_id' => $user->id,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->signInWithGoogle('chu.email@gmail.com');

        $this->assertDatabaseMissing('sessions', ['id' => 'phien-cua-ke-gia-mao']);
    }

    public function test_google_sign_in_keeps_the_password_of_a_verified_account(): void
    {
        $user = User::factory()->create(['email' => 'chu.email@gmail.com']);

        $this->signInWithGoogle('chu.email@gmail.com');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasPassword());
    }

    public function test_registration_is_rate_limited_per_ip(): void
    {
        Notification::fake();

        foreach (range(1, 21) as $i) {
            $response = $this->post('/register', [
                'name' => 'Bot',
                'email' => "bot{$i}@gmail.com",
                'password' => 'Bot@1234',
                'password_confirmation' => 'Bot@1234',
            ]);
            Auth::logout();
        }

        $response->assertSessionHasErrors('email');
        $this->assertSame(20, User::count());
    }

    public function test_password_reset_emails_are_rate_limited_per_address(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'hocvien@gmail.com']);

        foreach (range(1, 3) as $i) {
            $this->travel(2)->minutes();
            $this->post('/forgot-password', ['email' => 'hocvien@gmail.com'])->assertSessionHasNoErrors();
        }

        $this->travel(2)->minutes();
        $this->post('/forgot-password', ['email' => 'hocvien@gmail.com'])->assertSessionHasErrors('email');
    }

    public function test_learner_actions_are_rate_limited(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => CourseStatus::Published, 'published_at' => now()]);

        foreach (range(1, 20) as $i) {
            $this->actingAs($user)->post(route('wishlist.toggle', $course))->assertRedirect();
        }

        $this->actingAs($user)->post(route('wishlist.toggle', $course))->assertStatus(429);
    }

    public function test_deploy_command_replaces_public_demo_passwords(): void
    {
        config(['demo.admin_password' => null, 'demo.teacher_password' => 'RiengTu@2026', 'demo.student_password' => null]);

        $admin = User::factory()->create(['email' => 'admin@example.com', 'role' => UserRole::Admin, 'password' => 'Admin@123']);
        $changedAdmin = User::factory()->create(['email' => 'admin2@example.com', 'role' => UserRole::Admin, 'password' => 'DaDoi@2026']);
        $teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => UserRole::Teacher, 'password' => 'Teacher@123']);
        $realStudent = User::factory()->create(['email' => 'hocvien@gmail.com', 'password' => 'Student@123']);

        $this->artisan('app:secure-demo-accounts')->assertSuccessful();

        $this->assertFalse(Hash::check('Admin@123', $admin->fresh()->password));
        $this->assertTrue(Hash::check('DaDoi@2026', $changedAdmin->fresh()->password));
        $this->assertTrue(Hash::check('RiengTu@2026', $teacher->fresh()->password));
        $this->assertTrue(Hash::check('Student@123', $realStudent->fresh()->password));

        // Nothing to do again until DEMO_*_PASSWORD changes.
        $this->artisan('app:secure-demo-accounts')->expectsOutput('Demo accounts already secured, skipping.');
    }

    public function test_changing_an_approved_course_sends_it_back_for_review(): void
    {
        $course = Course::factory()->create(['status' => CourseStatus::Published, 'published_at' => now()->subWeek()]);
        $course->update(['slug' => Str::slug($course->title)]);
        $data = ['title' => $course->title, 'summary' => $course->summary, 'price' => $course->price, 'level' => $course->level->value];

        // Saving without any change keeps the course online.
        $this->actingAs($course->instructor)->put(route('instructor.courses.update', $course), $data)->assertRedirect();
        $this->assertSame(CourseStatus::Published, $course->fresh()->status);

        $this->put(route('instructor.courses.update', $course), [...$data, 'summary' => 'Nội dung đã đổi sau khi duyệt'])
            ->assertSessionHas('success', Course::RESUBMITTED_MESSAGE);

        $course->refresh();
        $this->assertSame(CourseStatus::Pending, $course->status);
        $this->assertNotNull($course->published_at);
        $this->get(route('courses.index'))->assertDontSee($course->title);

        // The admin approves it again.
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->patch(route('admin.courses.approve', $course))->assertRedirect();
        $this->assertSame(CourseStatus::Published, $course->fresh()->status);
    }

    public function test_changing_a_lesson_of_an_approved_course_sends_it_back_for_review(): void
    {
        $course = Course::factory()->create(['status' => CourseStatus::Published, 'published_at' => now()]);
        $section = $course->sections()->create(['title' => 'Chương 1', 'position' => 1]);
        $lesson = Lesson::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);

        $this->actingAs($course->instructor)->get(route('instructor.courses.lessons.edit', [$course, $lesson]))->assertSee('Chờ duyệt');

        $this->put(route('instructor.courses.lessons.update', [$course, $lesson]), [
            'section_id' => $section->id,
            'title' => $lesson->title,
            'content' => '<p><a href="https://lua-dao.example">Nhận quà</a></p>',
            'duration_minutes' => $lesson->duration_minutes,
        ])->assertSessionHas('success', Course::RESUBMITTED_MESSAGE);

        $this->assertSame(CourseStatus::Pending, $course->fresh()->status);
    }

    public function test_editing_a_draft_course_does_not_change_its_status(): void
    {
        $course = Course::factory()->create(['status' => CourseStatus::Draft]);

        $this->actingAs($course->instructor)->put(route('instructor.courses.update', $course), [
            'title' => 'Tên mới', 'summary' => 'Mô tả mới', 'price' => 0, 'level' => $course->level->value,
        ])->assertSessionHas('success', 'Đã lưu thay đổi khoá học.');

        $this->assertSame(CourseStatus::Draft, $course->fresh()->status);
    }

    public function test_pages_send_security_headers(): void
    {
        foreach (['/', '/trang-khong-ton-tai'] as $url) {
            $response = $this->get($url);

            $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
            $this->assertStringContainsString("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
            $response->assertHeaderMissing('Strict-Transport-Security');
        }

        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}
