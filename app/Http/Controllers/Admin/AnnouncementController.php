<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnnouncementRequest;
use App\Models\Announcement;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $announcements = Announcement::query()
            ->with('author')
            ->when($request->string('q')->toString(), fn ($query, string $keyword) => $query->where('title', 'like', "%{$keyword}%"))
            ->latest('published_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('admin.announcements.form', [
            'announcement' => new Announcement(['is_published' => true, 'published_at' => now()]),
        ]);
    }

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        Announcement::create([
            ...$request->safe()->only(['title', 'content', 'is_published']),
            'user_id' => $request->user()->id,
            'published_at' => $request->date('published_at') ?? now(),
            'slug' => UniqueSlug::for(Announcement::class, $request->string('title')),
            'image' => $request->file('image')?->store('announcements', 'media'),
        ]);

        return to_route('admin.announcements.index')->with('success', 'Đã đăng thông báo.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.form', compact('announcement'));
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $attributes = [
            ...$request->safe()->only(['title', 'content', 'is_published']),
            'published_at' => $request->date('published_at') ?? $announcement->published_at ?? now(),
            'slug' => UniqueSlug::for(Announcement::class, $request->string('title'), $announcement->id),
        ];

        if ($request->hasFile('image')) {
            if ($announcement->image) {
                Storage::disk('media')->delete($announcement->image);
            }

            $attributes['image'] = $request->file('image')->store('announcements', 'media');
        }

        $announcement->update($attributes);

        return to_route('admin.announcements.index')->with('success', 'Đã cập nhật thông báo.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        if ($announcement->image) {
            Storage::disk('media')->delete($announcement->image);
        }

        $announcement->delete();

        return back()->with('success', 'Đã xoá thông báo.');
    }
}
