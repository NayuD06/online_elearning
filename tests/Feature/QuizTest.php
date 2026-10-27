<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_builds_a_quiz_with_questions(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);

        $this->actingAs($course->instructor)->post(route('instructor.courses.quizzes.store', $course), [
            'section_id' => $section->id,
            'title' => 'Kiểm tra chương 1',
            'pass_percent' => 60,
        ])->assertRedirect();

        $quiz = $course->quizzes()->firstOrFail();
        $this->assertTrue($quiz->section->is($section));

        $this->post(route('instructor.courses.quizzes.questions.store', [$course, $quiz]), [
            'content' => 'PHP là gì?',
            'answers' => ['Ngôn ngữ lập trình', 'Hệ điều hành', '', ''],
            'correct_answer' => 0,
        ])->assertRedirect(route('instructor.courses.quizzes.edit', [$course, $quiz]));

        $question = $quiz->questions()->firstOrFail();
        $this->assertCount(2, $question->answers);
        $this->assertSame('Ngôn ngữ lập trình', $question->answers->firstWhere('is_correct')->content);

        // The correct answer must be one that was filled in.
        $this->post(route('instructor.courses.quizzes.questions.store', [$course, $quiz]), [
            'content' => 'Câu lỗi',
            'answers' => ['A', 'B', '', ''],
            'correct_answer' => 3,
        ])->assertSessionHasErrors('correct_answer');

        $this->put(route('instructor.courses.quizzes.questions.update', [$course, $quiz, $question]), [
            'content' => 'PHP là gì? (sửa)',
            'answers' => ['Ngôn ngữ lập trình', 'Hệ điều hành', 'Trình duyệt', ''],
            'correct_answer' => 2,
        ])->assertRedirect();

        $question->refresh();
        $this->assertCount(3, $question->answers);
        $this->assertSame('Trình duyệt', $question->answers->firstWhere('is_correct')->content);

        $this->get(route('instructor.courses.quizzes.edit', [$course, $quiz]))->assertOk()->assertSee('PHP là gì? (sửa)');
    }

    public function test_other_teachers_cannot_touch_the_quiz(): void
    {
        $quiz = Quiz::factory()->withQuestions()->create();
        $intruder = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($intruder)->get(route('instructor.courses.quizzes.edit', [$quiz->course, $quiz]))->assertForbidden();
        $this->delete(route('instructor.courses.quizzes.destroy', [$quiz->course, $quiz]))->assertForbidden();
    }

    public function test_enrolled_student_takes_quiz_and_sees_history(): void
    {
        $quiz = Quiz::factory()->withQuestions(4)->create();
        $course = $quiz->course;
        $student = User::factory()->create();
        $questions = $quiz->questions()->with('answers')->get();

        $this->actingAs($student)->get(route('quizzes.show', [$course, $quiz]))->assertForbidden();

        $student->enrollments()->create(['course_id' => $course->id]);
        $this->get(route('quizzes.show', [$course, $quiz]))->assertOk()->assertSee($questions->first()->content);

        // Three right answers, one wrong.
        $choices = $questions->mapWithKeys(fn ($question, $index) => [
            $question->id => $question->answers->where('is_correct', $index < 3)->first()->id,
        ])->all();

        $response = $this->post(route('quizzes.submit', [$course, $quiz]), ['answers' => $choices]);

        $attempt = $student->quizAttempts()->firstOrFail();
        $response->assertRedirect(route('quizzes.result', [$course, $quiz, $attempt]));
        $this->assertSame(3, $attempt->score);
        $this->assertSame(4, $attempt->total_questions);
        $this->assertSame(75, $attempt->percent);
        $this->assertTrue($attempt->isPassed());

        $this->get(route('quizzes.result', [$course, $quiz, $attempt]))->assertOk()->assertSee('3/4');
        $this->get(route('quizzes.show', [$course, $quiz]))->assertSee('Xem lại');
        $course->lessons()->create(['section_id' => $quiz->section_id, 'title' => 'Bài 1', 'position' => 1]);
        $this->get(route('learn.show', $course))->assertOk()->assertSee($quiz->title);

        // Somebody else's result stays private.
        $this->actingAs(User::factory()->create())->get(route('quizzes.result', [$course, $quiz, $attempt]))->assertForbidden();
    }

    public function test_answers_from_other_questions_do_not_count(): void
    {
        $quiz = Quiz::factory()->withQuestions(2)->create();
        $student = User::factory()->create();
        $student->enrollments()->create(['course_id' => $quiz->course_id]);
        [$first, $second] = $quiz->questions()->with('answers')->get()->all();

        $this->actingAs($student)->post(route('quizzes.submit', [$quiz->course, $quiz]), [
            'answers' => [$first->id => $second->answers->firstWhere('is_correct')->id],
        ]);

        $this->assertSame(0, $student->quizAttempts()->firstOrFail()->score);
    }

    public function test_quiz_of_another_course_is_not_reachable(): void
    {
        $quiz = Quiz::factory()->withQuestions()->create();
        $otherCourse = Course::factory()->create();
        $student = User::factory()->create();
        $student->enrollments()->create(['course_id' => $otherCourse->id]);

        $this->actingAs($student)->get(route('quizzes.show', [$otherCourse, $quiz]))->assertNotFound();
    }
}
