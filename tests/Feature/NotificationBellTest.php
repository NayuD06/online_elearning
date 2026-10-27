<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Notifications\CourseApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_review_and_questions_show_up_under_the_bell(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $course = Course::factory()->create(['status' => CourseStatus::Draft, 'published_at' => null]);
        $instructor = $course->instructor;
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        // Instructor submits → every admin is told.
        $this->actingAs($instructor)->post(route('instructor.courses.submit', $course));
        $this->assertSame('Khoá học mới chờ duyệt', $admin->notifications()->first()->data['title']);

        // Admin approves → instructor is told (email and bell).
        $this->actingAs($admin)->patch(route('admin.courses.approve', $course));
        $this->assertSame('Khoá học đã được duyệt', $instructor->notifications()->first()->data['title']);

        // A learner asks → the instructor is told; the instructor answers → the learner is told.
        $student = User::factory()->create();
        $this->actingAs($student)->post(route('enrollments.store', $course));
        $this->post(route('lesson-questions.store', [$course, $lesson]), ['body' => 'Cho em hỏi bài này ạ?']);
        $this->assertSame(3, $instructor->notifications()->count());

        $question = $lesson->questions()->first();
        $this->actingAs($instructor)->post(route('lesson-questions.answer', $question), ['body' => 'Em xem lại phút thứ 5 nhé.']);
        $this->assertSame('Câu hỏi của bạn đã có trả lời', $student->notifications()->first()->data['title']);

        $this->actingAs($student)->get(route('home'))
            ->assertOk()
            ->assertSee('Thông báo của tôi (1 chưa đọc)', false)
            ->assertSee('Câu hỏi của bạn đã có trả lời');
    }

    public function test_opening_a_notification_marks_it_read_and_follows_its_link(): void
    {
        $user = User::factory()->create(['role' => UserRole::Teacher]);
        $course = Course::factory()->create(['instructor_id' => $user->id]);
        $user->notify(new CourseApproved($course));
        $notification = $user->notifications()->first();

        $this->actingAs(User::factory()->create())->get(route('notifications.open', $notification->id))->assertNotFound();

        $this->actingAs($user)->get(route('notifications.open', $notification->id))->assertRedirect(route('courses.show', $course));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read_and_list_page(): void
    {
        $user = User::factory()->create(['role' => UserRole::Teacher]);
        $courses = Course::factory()->count(3)->create(['instructor_id' => $user->id]);
        $courses->each(fn (Course $course) => $user->notify(new CourseApproved($course)));

        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee($courses->first()->title);
        $this->post(route('notifications.read-all'))->assertSessionHas('success');

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_bell_notification_is_kept_when_the_email_fails(): void
    {
        Mail::extend('broken', fn () => new class implements TransportInterface
        {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new RuntimeException('Mail service refused');
            }

            public function __toString(): string
            {
                return 'broken';
            }
        });
        config(['mail.mailers.broken' => ['transport' => 'broken'], 'mail.default' => 'broken']);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $course = Course::factory()->create(['status' => CourseStatus::Pending, 'published_at' => null]);

        $this->actingAs($admin)->patch(route('admin.courses.approve', $course))->assertSessionHas('success');

        $this->assertSame(1, $course->instructor->notifications()->count());
    }
}
