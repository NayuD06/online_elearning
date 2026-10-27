<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_enroll_learn_and_complete_a_course(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        [$first, $second] = Lesson::factory()->count(2)->sequence(['position' => 1], ['position' => 2])->create(['course_id' => $course->id]);

        $this->actingAs($student)->get(route('learn.show', [$course, $second]))->assertForbidden();

        $this->post(route('enrollments.store', $course))->assertRedirect(route('learn.show', $course));
        $this->assertTrue($student->isEnrolledIn($course));

        $this->get(route('learn.show', $course))->assertOk()->assertSee($first->title);

        $this->post(route('learn.complete', [$course, $first]))->assertRedirect(route('learn.show', [$course, $second]));
        $this->assertSame(50, $student->progressIn($course));

        $this->post(route('learn.complete', [$course, $second]));
        $this->assertSame(100, $student->progressIn($course));
        $this->assertNotNull($student->enrollments()->first()->completed_at);
    }

    public function test_preview_lessons_are_open_to_everyone_signed_in(): void
    {
        $course = Course::factory()->create();
        $preview = Lesson::factory()->create(['course_id' => $course->id, 'is_preview' => true]);

        $this->actingAs(User::factory()->create())->get(route('learn.show', [$course, $preview]))->assertOk();
    }

    public function test_only_enrolled_students_can_review(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($student)->post(route('reviews.store', $course), ['rating' => 5, 'comment' => 'Tuyệt vời'])->assertForbidden();

        $student->enrollments()->create(['course_id' => $course->id]);

        $this->post(route('reviews.store', $course), ['rating' => 4, 'comment' => 'Tuyệt vời'])->assertRedirect();
        $this->post(route('reviews.store', $course), ['rating' => 5, 'comment' => 'Đã sửa'])->assertRedirect();

        $this->assertSame(1, $course->reviews()->count());
        $this->assertSame(5, $course->reviews()->first()->rating);
    }

    public function test_catalog_filters_and_paginates(): void
    {
        Course::factory()->count(12)->create(['level' => 'beginner', 'price' => 0]);
        $advanced = Course::factory()->create(['title' => 'Kiến trúc phần mềm nâng cao', 'level' => 'advanced', 'price' => 500000]);
        $draft = Course::factory()->draft()->create(['title' => 'Khoá học nháp bí mật']);

        $this->get(route('courses.index'))->assertOk()->assertDontSee($draft->title)->assertViewHas('courses', fn ($courses) => $courses->count() === 9 && $courses->total() === 13);

        $this->get(route('courses.index', ['level' => 'advanced']))->assertSee($advanced->title)->assertViewHas('courses', fn ($courses) => $courses->total() === 1);
        $this->get(route('courses.index', ['q' => 'Kiến trúc']))->assertViewHas('courses', fn ($courses) => $courses->total() === 1);
        $this->get(route('courses.index', ['price' => 'paid']))->assertViewHas('courses', fn ($courses) => $courses->total() === 1);
        $this->get(route('courses.show', $draft))->assertNotFound();
    }
}
