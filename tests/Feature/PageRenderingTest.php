<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_public_pages_render_for_guests(): void
    {
        $course = Course::published()->first();
        $announcement = Announcement::published()->first();

        foreach ([
            route('home'),
            route('courses.index'),
            route('courses.index', ['q' => 'Laravel', 'level' => 'beginner', 'sort' => 'popular', 'page' => 2]),
            route('courses.show', $course),
            route('announcements.index'),
            route('announcements.show', $announcement),
            route('login'),
            route('register'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_student_pages_render(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $course = $student->enrolledCourses()->first();

        $this->actingAs($student);

        $this->get(route('dashboard'))->assertOk()->assertSee($course->title);
        $this->get(route('learn.show', $course))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $pendingCourse = Course::where('status', CourseStatus::Pending)->firstOrFail();

        $this->actingAs($admin);

        foreach ([
            route('admin.dashboard'),
            route('admin.courses.index', ['q' => 'a', 'status' => 'published']),
            route('admin.courses.index', ['status' => 'pending']),
            route('admin.categories.index'),
            route('admin.categories.create'),
            route('admin.users.index', ['role' => 'teacher']),
            route('admin.users.create'),
            route('admin.users.edit', $admin),
            route('admin.announcements.index'),
            route('admin.announcements.create'),
            route('admin.announcements.edit', Announcement::first()),
            route('admin.reviews.index'),
            route('admin.enrollments.index'),
            route('courses.show', $pendingCourse),
            route('learn.show', $pendingCourse),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_instructor_pages_render(): void
    {
        $teacher = User::where('email', 'teacher@example.com')->firstOrFail();
        $course = $teacher->taughtCourses()->whereHas('enrollments')->firstOrFail();
        $lesson = $course->lessons()->first();
        $quiz = $course->quizzes()->first();

        $this->actingAs($teacher);

        foreach ([
            route('instructor.dashboard'),
            route('instructor.courses.index', ['q' => 'a', 'status' => 'published']),
            route('instructor.courses.create'),
            route('instructor.courses.edit', $course),
            route('instructor.courses.curriculum', $course),
            route('instructor.courses.students', $course),
            route('instructor.courses.lessons.create', $course),
            route('instructor.courses.lessons.edit', [$course, $lesson]),
            route('instructor.courses.quizzes.create', $course),
            route('instructor.courses.quizzes.edit', [$course, $quiz]),
            route('instructor.courses.quizzes.questions.create', [$course, $quiz]),
            route('instructor.courses.quizzes.questions.edit', [$course, $quiz, $quiz->questions()->first()]),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_seeded_demo_accounts_can_log_in(): void
    {
        foreach ([
            'admin@example.com' => ['Admin@123', 'admin.dashboard'],
            'teacher@example.com' => ['Teacher@123', 'instructor.dashboard'],
            'student@example.com' => ['Student@123', 'dashboard'],
        ] as $email => [$password, $home]) {
            $this->post('/login', ['email' => $email, 'password' => $password])->assertRedirect(route($home, absolute: false));
            $this->post('/logout');
        }
    }

    public function test_student_takes_seeded_quiz(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $course = $student->enrolledCourses()->first();
        $quiz = $course->quizzes()->first();

        $this->actingAs($student)->get(route('quizzes.show', [$course, $quiz]))->assertOk();
        $this->post(route('quizzes.submit', [$course, $quiz]), ['answers' => []])->assertRedirect();
    }
}
