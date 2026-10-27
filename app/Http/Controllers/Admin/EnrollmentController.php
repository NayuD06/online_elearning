<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $enrollments = Enrollment::query()
            ->with(['user', 'course'])
            ->when(! $user->isAdmin(), fn (Builder $query) => $query->whereHas('course', fn (Builder $query) => $query->where('instructor_id', $user->id)))
            ->when($request->string('q')->toString(), fn (Builder $query, string $keyword) => $query->where(
                fn (Builder $query) => $query->whereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"))
                    ->orWhereHas('course', fn (Builder $query) => $query->where('title', 'like', "%{$keyword}%"))
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.enrollments.index', compact('enrollments'));
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        Gate::authorize('manage', $enrollment->course);

        $enrollment->delete();

        return back()->with('success', 'Đã huỷ ghi danh của học viên.');
    }
}
