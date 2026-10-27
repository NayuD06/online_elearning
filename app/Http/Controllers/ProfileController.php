<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->except('avatar'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('media')->delete($user->avatar);
            }

            $user->avatar = $request->file('avatar')->store('avatars', 'media');
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Let a student (e.g. one who signed up with Google) ask an admin to become an instructor.
     */
    public function requestInstructor(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== UserRole::Student || $user->hasPendingInstructorRequest()) {
            return back()->with('error', 'Tài khoản của bạn không thể gửi yêu cầu làm giảng viên lúc này.');
        }

        $user->forceFill(['instructor_status' => 'pending', 'instructor_requested_at' => now()])->save();

        return back()->with('success', 'Đã gửi yêu cầu làm giảng viên. Quản trị viên sẽ xem xét và phản hồi qua email.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()->hasPassword()) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        }

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
