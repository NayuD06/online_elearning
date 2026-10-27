<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Response;

/**
 * sitemap.xml with every public page, so search engines find new courses quickly.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => null, 'priority' => '1.0'],
            ['loc' => route('courses.index'), 'lastmod' => Course::published()->max('updated_at'), 'priority' => '0.9'],
            ['loc' => route('announcements.index'), 'lastmod' => Announcement::published()->max('updated_at'), 'priority' => '0.5'],
            ['loc' => route('certificates.verify'), 'lastmod' => null, 'priority' => '0.3'],
        ])
            ->concat(Course::published()->latest('published_at')->get(['slug', 'updated_at'])->map(fn (Course $course) => [
                'loc' => route('courses.show', $course), 'lastmod' => $course->updated_at, 'priority' => '0.8',
            ]))
            ->concat(Announcement::published()->latest('published_at')->get(['slug', 'updated_at'])->map(fn (Announcement $announcement) => [
                'loc' => route('announcements.show', $announcement), 'lastmod' => $announcement->updated_at, 'priority' => '0.4',
            ]))
            ->concat(User::where('role', UserRole::Teacher)->whereNull('locked_at')->whereHas('taughtCourses', fn ($query) => $query->published())
                ->get(['id', 'updated_at'])->map(fn (User $instructor) => [
                    'loc' => route('instructors.show', $instructor), 'lastmod' => $instructor->updated_at, 'priority' => '0.5',
                ]));

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
