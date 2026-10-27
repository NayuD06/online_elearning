<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationToggleTest extends TestCase
{
    use RefreshDatabase;

    private function register(): User
    {
        $this->post('/register', [
            'name' => 'Học viên mới',
            'email' => 'moi@example.com',
            'password' => 'Matkhau@1',
            'password_confirmation' => 'Matkhau@1',
        ])->assertRedirect(route('dashboard', absolute: false));

        return User::where('email', 'moi@example.com')->firstOrFail();
    }

    public function test_registration_sends_verification_email_when_enabled(): void
    {
        Notification::fake();
        config(['auth.verify_email' => true]);

        $user = $this->register();

        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_verification_is_skipped_when_disabled(): void
    {
        Notification::fake();
        config(['auth.verify_email' => false]);

        $user = $this->register();

        Notification::assertNotSentTo($user, VerifyEmail::class);
        $this->get(route('dashboard'))->assertOk();
    }
}
