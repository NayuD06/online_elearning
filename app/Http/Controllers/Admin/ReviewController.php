<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $reviews = Review::query()
            ->with(['user', 'course'])
            ->when(! $user->isAdmin(), fn (Builder $query) => $query->whereHas('course', fn (Builder $query) => $query->where('instructor_id', $user->id)))
            ->when($request->integer('rating'), fn (Builder $query, int $rating) => $query->where('rating', $rating))
            ->when($request->string('q')->toString(), fn (Builder $query, string $keyword) => $query->where(
                fn (Builder $query) => $query->where('comment', 'like', "%{$keyword}%")
                    ->orWhereHas('course', fn (Builder $query) => $query->where('title', 'like', "%{$keyword}%"))
            ))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    /**
     * Toggle whether the review is shown on the course page.
     */
    public function update(Review $review): RedirectResponse
    {
        Gate::authorize('manage', $review->course);

        $review->update(['is_visible' => ! $review->is_visible]);

        return back()->with('success', $review->is_visible ? 'Đánh giá đã được hiển thị.' : 'Đánh giá đã được ẩn.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        Gate::authorize('manage', $review->course);

        $review->delete();

        return back()->with('success', 'Đã xoá đánh giá.');
    }
}
