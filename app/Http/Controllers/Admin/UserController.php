<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
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

        $user->update($attributes);

        return to_route('admin.users.index')->with('success', 'Đã cập nhật tài khoản.');
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
}
