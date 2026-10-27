<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

/**
 * A mail service outage (e.g. the mail API refusing a request) must not turn into a 500 page.
 */
class MailFailureTest extends TestCase
{
    use RefreshDatabase;

    private function failMail(): void
    {
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Mail service refused'));
    }

    public function test_forgot_password_shows_a_message_when_mail_fails(): void
    {
        $user = User::factory()->create();
        $this->failMail();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors(['email' => 'Chưa gửi được email đặt lại mật khẩu, vui lòng thử lại sau ít phút.']);
    }

    public function test_registration_completes_when_verification_mail_fails(): void
    {
        config(['auth.verify_email' => true]);
        $this->failMail();

        $this->post('/register', [
            'name' => 'Học viên mới',
            'email' => 'moi@example.com',
            'password' => 'Matkhau@1',
            'password_confirmation' => 'Matkhau@1',
        ])->assertRedirect(route('dashboard', absolute: false))->assertSessionHas('error');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'moi@example.com']);
    }

    public function test_resending_verification_shows_a_message_when_mail_fails(): void
    {
        config(['auth.verify_email' => true]);
        $user = User::factory()->unverified()->create();
        $this->failMail();

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('error');

        $this->get(route('verification.notice'))->assertSee('Chưa gửi được email xác minh');
    }
}
