<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);
    }

    private function fakeGoogleUser(string $email = 'hocvien@gmail.com', string $id = 'google-123', array $raw = []): void
    {
        $googleUser = (new SocialiteUser)->setRaw($raw)->map([
            'id' => $id,
            'name' => 'Nguyễn Văn A',
            'email' => $email,
            'avatar' => 'https://lh3.googleusercontent.com/a/photo.jpg',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);
    }

    public function test_login_and_register_screens_show_the_google_button(): void
    {
        $this->get('/login')->assertSee(route('auth.google.redirect'));
        $this->get('/register')->assertSee(route('auth.google.redirect'));
    }

    public function test_redirect_sends_the_user_to_google(): void
    {
        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth', $response->headers->get('Location'));
        $this->assertStringContainsString(urlencode('/auth/google/callback'), $response->headers->get('Location'));
    }

    public function test_redirect_explains_when_google_is_not_configured(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/auth/google/redirect')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_new_google_user_is_created_as_a_student_without_password(): void
    {
        $this->fakeGoogleUser();

        $response = $this->get('/auth/google/callback?code=abc&state=xyz');

        $user = User::where('email', 'hocvien@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::Student, $user->role);
        $this->assertSame('google-123', $user->google_id);
        $this->assertFalse($user->hasPassword());
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('https://lh3.googleusercontent.com/a/photo.jpg', $user->avatar_url);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_existing_account_with_same_email_is_linked_and_keeps_its_role(): void
    {
        $teacher = User::factory()->create(['email' => 'giangvien@gmail.com', 'role' => UserRole::Teacher]);
        $this->fakeGoogleUser('GiangVien@gmail.com');

        $response = $this->get('/auth/google/callback?code=abc&state=xyz');

        $this->assertAuthenticatedAs($teacher);
        $this->assertSame(1, User::count());
        $this->assertSame('google-123', $teacher->fresh()->google_id);
        $this->assertSame(UserRole::Teacher, $teacher->fresh()->role);
        $this->assertTrue($teacher->fresh()->hasPassword());
        $response->assertRedirect(route('instructor.dashboard', absolute: false));
    }

    public function test_admin_account_is_not_linked_by_email(): void
    {
        $admin = User::factory()->create(['email' => 'quantri@gmail.com', 'role' => UserRole::Admin]);
        $this->fakeGoogleUser('quantri@gmail.com');

        $this->get('/auth/google/callback?code=abc&state=xyz')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNull($admin->fresh()->google_id);
    }

    public function test_unverified_google_email_is_refused(): void
    {
        User::factory()->create(['email' => 'hocvien@gmail.com']);
        $this->fakeGoogleUser(raw: ['email_verified' => false]);

        $this->get('/auth/google/callback?code=abc&state=xyz')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_returning_google_user_is_found_by_google_id(): void
    {
        $user = User::factory()->create(['email' => 'cu@gmail.com', 'google_id' => 'google-123']);
        $this->fakeGoogleUser('moi@gmail.com');

        $this->get('/auth/google/callback?code=abc&state=xyz');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
    }

    public function test_cancelled_or_failed_google_login_returns_to_login_page(): void
    {
        $this->get('/auth/google/callback?error=access_denied')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        Socialite::shouldReceive('driver->user')->andThrow(new InvalidStateException);

        $this->get('/auth/google/callback?code=abc&state=wrong')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_google_user_without_password_cannot_log_in_with_password_form(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => 'google-123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'Password@123']);

        $this->assertGuest();
    }

    public function test_google_user_can_set_a_password_without_current_password(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => 'google-123']);

        $this->actingAs($user)->get('/profile')->assertOk()->assertDontSee('update_password_current_password');

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'password' => 'MatKhau@1',
                'password_confirmation' => 'MatKhau@1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue($user->fresh()->hasPassword());

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'MatKhau@1']);
        $this->assertAuthenticated();
    }

    public function test_google_user_without_password_can_delete_account(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => 'google-123']);

        $this->actingAs($user)->delete('/profile')->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }
}
