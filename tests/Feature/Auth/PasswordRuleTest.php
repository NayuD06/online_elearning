<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Ab@1'],
            'no uppercase' => ['abc@123'],
            'no special character' => ['Abc1234'],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function test_register_rejects_weak_passwords(string $password): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    #[DataProvider('weakPasswords')]
    public function test_login_rejects_passwords_that_break_the_rule(string $password): void
    {
        $user = User::factory()->create(['password' => $password]);

        $this->post('/login', ['email' => $user->email, 'password' => $password])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_strong_password_is_accepted(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Hoc@123',
            'password_confirmation' => 'Hoc@123',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }
}
