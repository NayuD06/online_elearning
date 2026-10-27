<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\InstructorRequestReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_registering_as_instructor_creates_a_student_waiting_for_approval(): void
    {
        $this->post('/register', [
            'name' => 'Giảng Viên Mới',
            'email' => 'gv-moi@example.com',
            'password' => 'Teacher@123',
            'password_confirmation' => 'Teacher@123',
            'account_type' => 'teacher',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'gv-moi@example.com')->first();

        $this->assertSame(UserRole::Student, $user->role);
        $this->assertTrue($user->hasPendingInstructorRequest());
        $this->get(route('instructor.dashboard'))->assertForbidden();
    }

    public function test_registering_as_student_sends_no_request(): void
    {
        $this->post('/register', [
            'name' => 'Học Viên',
            'email' => 'hv@example.com',
            'password' => 'Student@123',
            'password_confirmation' => 'Student@123',
        ]);

        $this->assertNull(User::where('email', 'hv@example.com')->value('instructor_status'));
    }

    public function test_registration_rejects_unknown_account_types(): void
    {
        $this->post('/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'Student@123',
            'password_confirmation' => 'Student@123',
            'account_type' => 'admin',
        ])->assertSessionHasErrors('account_type');

        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    public function test_admin_approves_an_instructor_request(): void
    {
        Notification::fake();
        $user = User::factory()->create(['instructor_status' => 'pending', 'instructor_requested_at' => now()]);

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['status' => 'instructor_pending']))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('Chờ duyệt giảng viên');

        $this->patch(route('admin.users.instructor.approve', $user))->assertSessionHas('success');

        $user->refresh();
        $this->assertSame(UserRole::Teacher, $user->role);
        $this->assertNull($user->instructor_status);
        Notification::assertSentTo($user, InstructorRequestReviewed::class, fn ($notification) => $notification->approved);

        $this->actingAs($user)->get(route('instructor.dashboard'))->assertOk();
    }

    public function test_admin_rejects_an_instructor_request_and_the_user_can_ask_again(): void
    {
        Notification::fake();
        $user = User::factory()->create(['instructor_status' => 'pending', 'instructor_requested_at' => now()]);

        $this->actingAs($this->admin())->patch(route('admin.users.instructor.reject', $user));

        $user->refresh();
        $this->assertSame(UserRole::Student, $user->role);
        $this->assertSame('rejected', $user->instructor_status);
        Notification::assertSentTo($user, InstructorRequestReviewed::class, fn ($notification) => ! $notification->approved);

        $this->actingAs($user)->get(route('profile.edit'))->assertSee('Gửi yêu cầu làm giảng viên');
        $this->post(route('profile.instructor-request'))->assertSessionHas('success');
        $this->assertTrue($user->fresh()->hasPendingInstructorRequest());
    }

    public function test_approving_still_works_when_the_email_cannot_be_sent(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);
        $user = User::factory()->create(['instructor_status' => 'pending']);

        $this->actingAs($this->admin())->patch(route('admin.users.instructor.approve', $user))->assertSessionHas('success');

        $this->assertSame(UserRole::Teacher, $user->fresh()->role);
    }

    public function test_teachers_and_admins_cannot_send_instructor_requests(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($teacher)->post(route('profile.instructor-request'))->assertSessionHas('error');
        $this->assertNull($teacher->fresh()->instructor_status);
    }

    public function test_only_admins_review_instructor_requests(): void
    {
        $user = User::factory()->create(['instructor_status' => 'pending']);

        $this->actingAs(User::factory()->create(['role' => UserRole::Teacher]))
            ->patch(route('admin.users.instructor.approve', $user))
            ->assertForbidden();

        $this->assertSame(UserRole::Student, $user->fresh()->role);
    }

    public function test_locked_user_cannot_sign_in_with_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($admin = $this->admin())->patch(route('admin.users.lock', $user))->assertSessionHas('success');
        $this->assertTrue($user->fresh()->isLocked());
        $this->post('/logout');

        $this->post('/login', ['email' => $user->email, 'password' => 'Password@123'])
            ->assertSessionHasErrors(['email' => User::LOCKED_MESSAGE]);
        $this->assertGuest();

        $this->actingAs($admin)->patch(route('admin.users.lock', $user));
        $this->assertFalse($user->fresh()->isLocked());
        $this->post('/logout');

        $this->post('/login', ['email' => $user->email, 'password' => 'Password@123']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_signed_in_user_is_signed_out_once_locked(): void
    {
        $user = User::factory()->create(['locked_at' => now()]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_locked_user_cannot_sign_in_with_google(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $user = User::factory()->create(['email' => 'khoa@gmail.com', 'locked_at' => now()]);

        Socialite::shouldReceive('driver->user')->andReturn((new SocialiteUser)->setRaw([])->map([
            'id' => 'google-1', 'name' => 'Khoá', 'email' => 'khoa@gmail.com', 'avatar' => null,
        ]));

        $this->get('/auth/google/callback?code=abc')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => User::LOCKED_MESSAGE]);

        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_admin_cannot_lock_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.users.lock', $admin))->assertSessionHas('error');
        $this->assertFalse($admin->fresh()->isLocked());
    }

    public function test_users_page_filters_locked_accounts(): void
    {
        $locked = User::factory()->create(['locked_at' => now()]);
        $active = User::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['status' => 'locked']))
            ->assertSee($locked->email)
            ->assertDontSee($active->email);
    }
}
