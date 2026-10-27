<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use App\Notifications\CourseApproved;
use App\Notifications\CourseRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class CourseReviewEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_is_emailed_when_course_is_approved(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $course = Course::factory()->create(['status' => CourseStatus::Pending, 'published_at' => null]);

        $this->actingAs($admin)->patch(route('admin.courses.approve', $course))->assertSessionHas('success');

        Notification::assertSentTo($course->instructor, CourseApproved::class, function (CourseApproved $notification) use ($course) {
            $mail = $notification->toMail($course->instructor);

            return str_contains($mail->subject, $course->title) && $mail->actionUrl === route('courses.show', $course);
        });
    }

    public function test_showing_a_hidden_course_again_sends_no_email(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $course = Course::factory()->create(['status' => CourseStatus::Hidden]);

        $this->actingAs($admin)->patch(route('admin.courses.approve', $course))->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_instructor_is_emailed_the_rejection_reason(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $course = Course::factory()->create(['status' => CourseStatus::Pending, 'published_at' => null]);

        $this->actingAs($admin)
            ->patch(route('admin.courses.reject', $course), ['rejection_reason' => 'Thiếu đề cương'])
            ->assertSessionHas('success');

        Notification::assertSentTo($course->instructor, CourseRejected::class, function (CourseRejected $notification) use ($course) {
            $mail = $notification->toMail($course->instructor);

            return in_array('Thiếu đề cương', $mail->introLines, true)
                && $mail->actionUrl === route('instructor.courses.edit', $course);
        });
    }

    public function test_review_still_succeeds_when_email_cannot_be_sent(): void
    {
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Mail server down'));
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $course = Course::factory()->create(['status' => CourseStatus::Pending, 'published_at' => null]);

        $this->actingAs($admin)->patch(route('admin.courses.approve', $course))->assertSessionHas('success');

        $this->assertSame(CourseStatus::Published, $course->fresh()->status);
    }
}
