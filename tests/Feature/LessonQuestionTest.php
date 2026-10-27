<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Models\User;
use App\Notifications\LessonQuestionAnswered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LessonQuestionTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Lesson $lesson;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->create();
        $this->lesson = Lesson::factory()->create(['course_id' => $this->course->id]);
        $this->student = User::factory()->create();
        $this->student->enrollments()->create(['course_id' => $this->course->id]);
    }

    private function ask(string $body = 'Em chưa hiểu phần route model binding ạ?'): LessonQuestion
    {
        $this->actingAs($this->student)
            ->post(route('lesson-questions.store', [$this->course, $this->lesson]), ['body' => $body])
            ->assertRedirect();

        return LessonQuestion::latest('id')->firstOrFail();
    }

    public function test_enrolled_student_asks_and_the_instructor_answers(): void
    {
        Notification::fake();
        $question = $this->ask();

        $this->get(route('learn.show', [$this->course, $this->lesson]))->assertSee($question->body)->assertSee('Chưa trả lời');

        $this->actingAs($this->course->instructor)
            ->get(route('instructor.discussions.index'))
            ->assertOk()
            ->assertSee($question->body);

        $this->post(route('lesson-questions.answer', $question), ['body' => 'Em xem lại mục 2 nhé.'])->assertSessionHas('success');

        $this->assertSame(1, $question->answers()->count());
        Notification::assertSentTo($this->student, LessonQuestionAnswered::class);

        // Answered questions leave the default "unanswered" inbox.
        $this->get(route('instructor.discussions.index'))->assertDontSee($question->body);
        $this->get(route('instructor.discussions.index', ['status' => 'all']))->assertSee('Em xem lại mục 2 nhé.');

        $this->actingAs($this->student)->get(route('learn.show', [$this->course, $this->lesson]))->assertSee('Em xem lại mục 2 nhé.');
    }

    public function test_students_who_are_not_enrolled_cannot_ask(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('lesson-questions.store', [$this->course, $this->lesson]), ['body' => 'Cho hỏi?'])
            ->assertForbidden();

        $this->assertSame(0, LessonQuestion::count());
    }

    public function test_students_and_other_instructors_cannot_answer(): void
    {
        $question = $this->ask();
        $otherTeacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($this->student)->post(route('lesson-questions.answer', $question), ['body' => 'Tự trả lời'])->assertForbidden();
        $this->actingAs($otherTeacher)->post(route('lesson-questions.answer', $question), ['body' => 'Không phải lớp mình'])->assertForbidden();
        $this->actingAs($otherTeacher)->get(route('instructor.discussions.index'))->assertDontSee($question->body);

        $this->assertSame(0, $question->answers()->count());
    }

    public function test_admin_sees_and_answers_every_question(): void
    {
        Notification::fake();
        $question = $this->ask();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('admin.discussions.index'))->assertOk()->assertSee($question->body);
        $this->post(route('lesson-questions.answer', $question), ['body' => 'Quản trị viên trả lời'])->assertSessionHas('success');

        $this->assertSame(1, $question->answers()->count());
    }

    public function test_author_can_delete_own_question_but_not_others(): void
    {
        $question = $this->ask();
        $classmate = User::factory()->create();
        $classmate->enrollments()->create(['course_id' => $this->course->id]);

        $this->actingAs($classmate)->delete(route('lesson-questions.destroy', $question))->assertForbidden();
        $this->actingAs($this->student)->delete(route('lesson-questions.destroy', $question))->assertSessionHas('success');

        $this->assertModelMissing($question);
    }

    public function test_question_body_is_escaped(): void
    {
        $this->ask('<script>alert(1)</script>');

        $this->get(route('learn.show', [$this->course, $this->lesson]))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
