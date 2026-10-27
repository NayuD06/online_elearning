<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'account_type' => ['nullable', Rule::in(['student', 'teacher'])],
        ], attributes: ['account_type' => 'loại tài khoản']);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Everyone starts as a student; an instructor account only opens once an admin approves the request.
        $wantsToTeach = $request->input('account_type') === 'teacher';

        if ($wantsToTeach) {
            $user->forceFill(['instructor_status' => 'pending', 'instructor_requested_at' => now()])->save();
        }

        $mailFailed = false;

        try {
            event(new Registered($user));
        } catch (Throwable $e) {
            // The account exists; the verification email can be resent later.
            report($e);
            $mailFailed = true;
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false))
            ->with($mailFailed ? ['error' => 'Tài khoản đã được tạo nhưng chưa gửi được email xác minh. Bạn có thể bấm gửi lại sau ít phút.'] : [])
            ->with($wantsToTeach ? ['success' => 'Đã gửi yêu cầu làm giảng viên. Trong lúc chờ quản trị viên duyệt, bạn vẫn học được như học viên.'] : []);
    }
}
