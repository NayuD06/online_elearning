<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        return view('admin.banners.index', [
            'banners' => Banner::orderBy('position')->orderBy('id')->paginate(20),
            'showingCount' => Banner::showing()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.banners.form', [
            'banner' => new Banner([
                'is_active' => true,
                'position' => (int) Banner::max('position') + 1,
            ]),
        ]);
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        Banner::create([
            ...$request->safe()->except('image'),
            'position' => $request->integer('position'),
            'image' => $request->file('image')->store('banners', 'media'),
        ]);

        return to_route('admin.banners.index')->with('success', 'Đã thêm banner.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', compact('banner'));
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $attributes = [
            ...$request->safe()->except('image'),
            'position' => $request->integer('position'),
        ];

        $previousImage = $banner->image;

        if ($request->hasFile('image')) {
            $attributes['image'] = $request->file('image')->store('banners', 'media');
        }

        $banner->update($attributes);

        if ($banner->image !== $previousImage) {
            Storage::disk('media')->delete($previousImage);
        }

        return to_route('admin.banners.index')->with('success', 'Đã cập nhật banner.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        Storage::disk('media')->delete($banner->image);
        $banner->delete();

        return back()->with('success', 'Đã xoá banner.');
    }
}
