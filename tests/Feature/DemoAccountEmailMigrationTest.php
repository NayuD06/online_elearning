<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccountEmailMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_gmail_demo_accounts_are_moved_to_example_domain(): void
    {
        $admin = User::factory()->create(['email' => 'admin@gmail.com']);
        $teacher = User::factory()->create(['email' => 'teacher@gmail.com']);
        User::factory()->create(['email' => 'student@example.com']);
        $student = User::factory()->create(['email' => 'student@gmail.com']);
        $other = User::factory()->create(['email' => 'ban.than@gmail.com']);

        $migration = require database_path('migrations/2026_10_26_000005_move_demo_accounts_to_example_domain.php');
        $migration->up();

        $this->assertSame('admin@example.com', $admin->fresh()->email);
        $this->assertSame('teacher@example.com', $teacher->fresh()->email);
        $this->assertSame('student@gmail.com', $student->fresh()->email);
        $this->assertSame('ban.than@gmail.com', $other->fresh()->email);
    }
}
