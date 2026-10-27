<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Notifications\InstructorRequestReviewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount(['enrollments', 'taughtCourses'])
            ->when($request->string('q')->toString(), fn ($query, string $keyword) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")
            ))
            ->when($request->string('role')->toString(), fn ($query, string $role) => $query->where('role', $role))
            ->when($request->string('status')->toString() === 'instructor_pending', fn ($query) => $query->where('instructor_status', 'pending'))
            ->when($request->string('status')->toString() === 'locked', fn ($query) => $query->whereNotNull('locked_at'))
            ->orderByRaw("case when instructor_status = 'pending' then 0 else 1 end")
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'pendingInstructorCount' => User::where('instructor_status', 'pending')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User, 'roles' => UserRole::cases()]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::create([
            ...$request->safe()->only(['name', 'email', 'role', 'phone', 'password']),
            'avatar' => $request->file('avatar')?->store('avatars', 'media'),
            'email_verified_at' => now(),
        ]);

        return to_route('admin.users.index')->with('success', 'Đã tạo tài khoản mới.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user, 'roles' => UserRole::cases()]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $attributes = $request->safe()->only(['name', 'email', 'role', 'phone']);

        if ($user->is($request->user())) {
            $attributes['role'] = $user->role;
        }

        if ($request->filled('password')) {
            $attributes['password'] = $request->input('password');
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('media')->delete($user->avatar);
            }

            $attributes['avatar'] = $request->file('avatar')->store('avatars', 'media');
        }

        // Promoting a user by hand also settles a pending request to teach.
        if (($attributes['role'] ?? null) === UserRole::Teacher->value) {
            $user->instructor_status = null;
        }

        $user->update($attributes);

        return to_route('admin.users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    /**
     * Accept a student's request to teach: the account becomes an instructor account.
     */
    public function approveInstructor(User $user): RedirectResponse
    {
        if (! $user->hasPendingInstructorRequest()) {
            return back()->with('error', 'Tài khoản này không có yêu cầu làm giảng viên đang chờ duyệt.');
        }

        $user->forceFill(['role' => UserRole::Teacher, 'instructor_status' => null])->save();
        $this->notify($user, new InstructorRequestReviewed(approved: true));

        return back()->with('success', "Đã duyệt {$user->name} làm giảng viên.");
    }

    public function rejectInstructor(User $user): RedirectResponse
    {
        if (! $user->hasPendingInstructorRequest()) {
            return back()->with('error', 'Tài khoản này không có yêu cầu làm giảng viên đang chờ duyệt.');
        }

        $user->forceFill(['instructor_status' => 'rejected'])->save();
        $this->notify($user, new InstructorRequestReviewed(approved: false));

        return back()->with('success', "Đã từ chối yêu cầu làm giảng viên của {$user->name}.");
    }

    /**
     * Lock or unlock an account. A locked user is signed out and cannot sign in again (password or Google).
     */
    public function toggleLock(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Bạn không thể tự khoá tài khoản của mình.');
        }

        $locking = ! $user->isLocked();
        $user->forceFill([
            'locked_at' => $locking ? now() : null,
            // Drop the "remember me" token so a locked user cannot come back with an old cookie.
            ...($locking ? ['remember_token' => null] : []),
        ])->save();

        return back()->with('success', $locking ? "Đã khoá tài khoản {$user->name}." : "Đã mở khoá tài khoản {$user->name}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Bạn không thể tự xoá tài khoản của mình.');
        }

        if ($user->avatar) {
            Storage::disk('media')->delete($user->avatar);
        }

        $user->delete();

        return back()->with('success', 'Đã xoá tài khoản.');
    }

    private function notify(User $user, Notification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable $e) {
            // The decision is saved even when the email cannot be sent.
            report($e);
        }
    }
}
