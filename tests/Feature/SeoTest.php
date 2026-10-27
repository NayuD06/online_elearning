<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_page_has_meta_open_graph_and_structured_data(): void
    {
        $course = Course::factory()->create(['title' => 'Thiết kế UI/UX', 'summary' => 'Học thiết kế giao diện từ con số 0.']);

        $this->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('<title>Thiết kế UI/UX | '.config('app.name').'</title>', false)
            ->assertSee('<meta name="description" content="Học thiết kế giao diện từ con số 0.">', false)
            ->assertSee('<meta property="og:title" content="Thiết kế UI/UX">', false)
            ->assertSee('<meta property="og:image" content="'.asset('images/og-default.png').'">', false)
            ->assertSee('<link rel="canonical" href="'.route('courses.show', $course).'">', false)
            ->assertSee('"@type":"Course"', false);
    }

    public function test_private_pages_are_not_indexed(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get(route('home'))->assertDontSee('noindex', false);
    }

    public function test_sitemap_lists_public_pages_only(): void
    {
        $published = Course::factory()->create();
        $draft = Course::factory()->draft()->create();
        $announcement = Announcement::factory()->create(['is_published' => true, 'published_at' => now()->subDay()]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee(route('courses.show', $published), false)
            ->assertSee(route('announcements.show', $announcement), false)
            ->assertSee(route('instructors.show', $published->instructor), false)
            ->assertDontSee(route('courses.show', $draft), false);
    }
}
