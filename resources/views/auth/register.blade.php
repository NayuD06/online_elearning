<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Tạo tài khoản</h1>
        <p class="mt-1 text-sm text-gray-500">Đăng ký miễn phí để ghi danh và theo dõi tiến độ học tập.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Account type: teachers are approved by an admin -->
        <fieldset class="mt-4">
            <legend class="text-sm font-medium text-gray-700">Bạn muốn tham gia với vai trò</legend>
            <div class="mt-2 grid grid-cols-2 gap-3">
                @foreach (['student' => ['academic', 'Học viên', 'Ghi danh và học ngay'], 'teacher' => ['users', 'Giảng viên', 'Cần quản trị viên duyệt']] as $value => [$icon, $label, $hint])
                    <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-gray-200 p-3 text-sm has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                        <input type="radio" name="account_type" value="{{ $value }}" @checked(old('account_type', 'student') === $value) class="mt-0.5 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="flex items-center gap-1 font-semibold text-gray-900"><x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}</span>
                            <span class="block text-xs text-gray-500">{{ $hint }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('account_type')" class="mt-2" />
        </fieldset>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required minlength="6" autocomplete="new-password" />
            <p class="mt-1 text-xs text-gray-500">Ít nhất 6 ký tự, gồm ít nhất 1 chữ in hoa và 1 ký tự đặc biệt (VD: Hoc@123).</p>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="mt-6 w-full py-2.5">
            {{ __('Register') }}
        </x-primary-button>
    </form>

    <x-google-sign-in-button label="Đăng ký bằng Google" />

    <p class="mt-6 text-center text-sm text-gray-600">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-900">{{ __('Log in') }}</a>
    </p>
</x-guest-layout>
