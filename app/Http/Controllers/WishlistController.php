<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    /**
     * Courses the learner saved for later.
     */
    public function index(Request $request): View
    {
        return view('wishlist.index', [
            'courses' => $request->user()->wishlist()
                ->published()
                ->with('category')
                ->withCount(['lessons', 'enrollments'])
                ->withAvg(['reviews' => fn ($query) => $query->where('is_visible', true)], 'rating')
                ->latest('wishlists.created_at')
                ->paginate(9),
        ]);
    }

    /**
     * Add the course to the wishlist, or take it out when it is already there.
     */
    public function toggle(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasInWishlist($course)) {
            $user->wishlist()->detach($course);

            return back()->with('success', 'Đã bỏ khoá học khỏi danh sách yêu thích.');
        }

        abort_unless($course->isPublished(), 404);

        $user->wishlist()->attach($course);

        return back()->with('success', 'Đã thêm khoá học vào danh sách yêu thích.');
    }
}
