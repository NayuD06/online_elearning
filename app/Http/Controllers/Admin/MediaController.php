<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\CourseImage;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use League\Flysystem\StorageAttributes;
use Throwable;

/**
 * Lists the images on the "media" disk (Cloudinary, or storage/app/public locally)
 * and where each one is used.
 */
class MediaController extends Controller
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg'];

    /**
     * Top-level folders on the disk and what they hold.
     */
    public const FOLDERS = [
        'avatars' => 'Ảnh đại diện',
        'courses' => 'Ảnh khoá học',
        'editor' => 'Ảnh trong bài viết',
        'announcements' => 'Ảnh thông báo',
    ];

    public function index(Request $request): View
    {
        $disk = Storage::disk('media');
        $error = null;

        try {
            $files = collect($disk->getDriver()->listContents('', true))
                ->filter(fn (StorageAttributes $item) => $item->isFile()
                    && in_array(strtolower(pathinfo($item->path(), PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true))
                ->map(fn (StorageAttributes $item) => [
                    'path' => $item->path(),
                    'name' => basename($item->path()),
                    'folder' => strtok($item->path(), '/'),
                    'size' => $item->fileSize(),
                    'modified' => $item->lastModified(),
                ])
                ->values();
        } catch (Throwable $e) {
            report($e);
            $error = $e->getMessage();
            $files = collect();
        }

        $usages = $this->usages($files);
        $files = $files->map(fn (array $file) => $file + [
            'url' => $disk->url($file['path']),
            'usages' => $usages[self::key($file['path'])] ?? [],
        ]);

        $stats = [
            'count' => $files->count(),
            'size' => $files->sum('size'),
            'unused' => $files->where('usages', [])->count(),
        ];

        $folder = $request->string('folder')->toString();
        $filtered = $files
            ->when(array_key_exists($folder, self::FOLDERS), fn (Collection $files) => $files->where('folder', $folder))
            ->when($request->boolean('unused'), fn (Collection $files) => $files->where('usages', []))
            ->when($request->string('q')->toString(), fn (Collection $files, string $keyword) => $files->filter(
                fn (array $file) => str_contains(mb_strtolower($file['path'].' '.collect($file['usages'])->pluck('label')->implode(' ')), mb_strtolower($keyword))
            ))
            ->sortByDesc('modified')
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $images = new LengthAwarePaginator($filtered->forPage($page, 24)->values(), $filtered->count(), 24, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $config = config('filesystems.disks.media');

        return view('admin.media.index', [
            'images' => $images,
            'stats' => $stats,
            'error' => $error,
            'folders' => self::FOLDERS,
            'onCloudinary' => $config['driver'] === 'cloudinary',
            'cloudName' => $config['driver'] === 'cloudinary' ? parse_url($config['url'], PHP_URL_HOST) : null,
            'cloudFolder' => $config['folder'] ?? null,
        ]);
    }

    /**
     * Where each file is used, keyed by its path without extension.
     *
     * @param  Collection<int, array<string, mixed>>  $files
     * @return array<string, list<array{label: string, url: string}>>
     */
    private function usages(Collection $files): array
    {
        $usages = [];
        $add = function (?string $path, string $label, string $url) use (&$usages): void {
            if ($path && ! str_starts_with($path, 'http')) {
                $usages[self::key($path)][] = compact('label', 'url');
            }
        };

        User::whereNotNull('avatar')->get(['id', 'name', 'avatar'])
            ->each(fn (User $user) => $add($user->avatar, "Ảnh đại diện của {$user->name}", route('admin.users.edit', $user)));

        Course::whereNotNull('thumbnail')->get(['id', 'title', 'thumbnail'])
            ->each(fn (Course $course) => $add($course->thumbnail, "Ảnh bìa khoá \"{$course->title}\"", route('admin.courses.index', ['q' => $course->title])));

        CourseImage::with('course:id,title')->get()
            ->each(fn (CourseImage $image) => $add($image->path, "Ảnh khoá \"{$image->course?->title}\"", route('admin.courses.index', ['q' => $image->course?->title])));

        Announcement::whereNotNull('image')->get()
            ->each(fn (Announcement $announcement) => $add($announcement->image, "Ảnh thông báo \"{$announcement->title}\"", route('admin.announcements.edit', $announcement)));

        // Editor images live inside HTML content, so look for their file name there.
        $editorFiles = $files->where('folder', 'editor');

        if ($editorFiles->isNotEmpty()) {
            $documents = collect()
                ->merge(Course::where('description', 'like', '%editor/%')->get(['id', 'title', 'description'])
                    ->map(fn (Course $course) => [$course->description, "Trong mô tả khoá \"{$course->title}\"", route('admin.courses.index', ['q' => $course->title])]))
                ->merge(Lesson::with('course:id,title')->where('content', 'like', '%editor/%')->get(['id', 'course_id', 'title', 'content'])
                    ->map(fn (Lesson $lesson) => [$lesson->content, "Trong bài học \"{$lesson->title}\"", route('admin.courses.index', ['q' => $lesson->course?->title])]))
                ->merge(Announcement::where('content', 'like', '%editor/%')->get()
                    ->map(fn (Announcement $announcement) => [$announcement->content, "Trong thông báo \"{$announcement->title}\"", route('admin.announcements.edit', $announcement)]));

            foreach ($editorFiles as $file) {
                $name = pathinfo($file['path'], PATHINFO_FILENAME);

                foreach ($documents as [$html, $label, $url]) {
                    if (str_contains((string) $html, $name)) {
                        $usages[self::key($file['path'])][] = compact('label', 'url');
                    }
                }
            }
        }

        return $usages;
    }

    /**
     * Cloudinary may report "photo.jpeg" as "photo.jpg", so match files without their extension.
     */
    private static function key(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension === '' ? $path : substr($path, 0, -strlen($extension) - 1);
    }
}
