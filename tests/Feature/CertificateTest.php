<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Notifications\CertificateIssued;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private function finishCourse(User $student, Course $course): void
    {
        foreach ($course->lessons as $lesson) {
            $this->actingAs($student)->post(route('learn.complete', [$course, $lesson]));
        }
    }

    public function test_finishing_every_lesson_issues_a_certificate(): void
    {
        Notification::fake();
        $student = User::factory()->create(['name' => 'Nguyễn Thị Ánh']);
        $course = Course::factory()->create(['title' => 'Lập trình Laravel cơ bản']);
        [$first, $last] = Lesson::factory()->count(2)->sequence(['position' => 1], ['position' => 2])->create(['course_id' => $course->id]);
        $student->enrollments()->create(['course_id' => $course->id]);

        $this->actingAs($student)->post(route('learn.complete', [$course, $first]));
        $this->assertSame(0, Certificate::count());

        $response = $this->post(route('learn.complete', [$course, $last]));

        $certificate = Certificate::sole();
        $response->assertRedirect(route('certificates.show', $certificate));
        $this->assertMatchesRegularExpression('/^UTH-[2-9A-Z]{4}-[2-9A-Z]{4}$/', $certificate->code);
        $this->assertSame('Nguyễn Thị Ánh', $certificate->student_name);
        $this->assertSame('Lập trình Laravel cơ bản', $certificate->course_title);
        Notification::assertSentTo($student, CertificateIssued::class);

        // Unmarking and finishing again keeps the same certificate.
        $this->post(route('learn.complete', [$course, $last]));
        $this->post(route('learn.complete', [$course, $last]));
        $this->assertSame($certificate->code, Certificate::sole()->code);
        Notification::assertSentToTimes($student, CertificateIssued::class, 1);
    }

    public function test_instructor_previewing_their_own_course_gets_no_certificate(): void
    {
        $course = Course::factory()->create();
        Lesson::factory()->create(['course_id' => $course->id]);

        $this->finishCourse($course->instructor, $course->load('lessons'));

        $this->assertSame(0, Certificate::count());
    }

    public function test_verification_page_is_public_and_finds_codes(): void
    {
        $certificate = Certificate::issueFor(User::factory()->create(['name' => 'Trần Văn Bình']), Course::factory()->create());

        $this->get(route('certificates.show', $certificate))
            ->assertOk()
            ->assertSee('Chứng chỉ hợp lệ')
            ->assertSee('Trần Văn Bình')
            ->assertSee('property="og:title"', false)
            ->assertDontSee('Tải chứng chỉ (PDF)');

        $this->get(route('certificates.verify', ['code' => ' '.strtolower($certificate->code).' ']))
            ->assertRedirect(route('certificates.show', $certificate));

        $this->get(route('certificates.verify', ['code' => 'UTH-0000-0000']))
            ->assertOk()
            ->assertSee('Không tìm thấy chứng chỉ');

        $this->get('/chung-chi/UTH-0000-0000')->assertNotFound();
    }

    public function test_only_the_owner_or_an_admin_downloads_the_pdf(): void
    {
        $student = User::factory()->create(['name' => 'Lê Hoàng Yến']);
        $certificate = Certificate::issueFor($student, Course::factory()->create());

        $this->actingAs(User::factory()->create())->get(route('certificates.download', $certificate))->assertForbidden();

        $response = $this->actingAs($student)->get(route('certificates.download', $certificate));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString("chung-chi-{$certificate->code}.pdf", $response->headers->get('content-disposition'));

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(route('certificates.download', $certificate))
            ->assertOk();
    }

    public function test_my_certificates_backfills_courses_finished_earlier(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $student->enrollments()->create(['course_id' => $course->id, 'completed_at' => now()->subWeek()]);

        $this->actingAs($student)->get(route('certificates.index'))->assertOk()->assertSee($course->title);

        $this->assertTrue($student->certificates()->where('course_id', $course->id)->exists());
    }
}
