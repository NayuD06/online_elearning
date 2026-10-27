<x-admin-layout :title="$user->exists ? 'Sửa người dùng' : 'Thêm người dùng'">
    <form method="POST" enctype="multipart/form-data" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="grid max-w-5xl gap-6 lg:grid-cols-3">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <div class="card space-y-5 p-6 lg:col-span-2">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="form-label">Họ tên <span class="text-rose-500">*</span></label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="form-input">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="form-label">Email <span class="text-rose-500">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input">
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="form-label">Số điện thoại</label>
                    <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="form-input">
                    @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="role" class="form-label">Vai trò</label>
                    <select id="role" name="role" class="form-input" @disabled($user->is(Auth::user()))>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $user->role?->value ?? 'student') === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    @if ($user->is(Auth::user()))
                        <input type="hidden" name="role" value="{{ $user->role->value }}">
                        <p class="mt-1 text-xs text-gray-500">Bạn không thể tự đổi vai trò của mình.</p>
                    @endif
                    @error('role') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="form-label">Mật khẩu @unless ($user->exists)<span class="text-rose-500">*</span>@endunless</label>
                    <input id="password" type="password" name="password" autocomplete="new-password" class="form-input" @required(! $user->exists)>
                    @if ($user->exists) <p class="mt-1 text-xs text-gray-500">Để trống nếu không đổi.</p> @endif
                    @error('password') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="form-label">Nhập lại mật khẩu</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="form-input">
                </div>
            </div>
            <div class="flex gap-2">
                <button class="btn-primary">{{ $user->exists ? 'Lưu thay đổi' : 'Tạo tài khoản' }}</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Quay lại</a>
            </div>
        </div>

        <div class="card h-fit p-6 text-center" x-data="imagePreview">
            <img :src="previews[0] ?? @js($user->avatar_url ?? 'https://ui-avatars.com/api/?name=User&background=0f766e&color=fff')" alt="" class="mx-auto h-28 w-28 rounded-full object-cover ring-4 ring-brand-100">
            <label class="btn-secondary mt-4 cursor-pointer">
                <x-icon name="photo" class="h-4 w-4" /> Chọn ảnh đại diện
                <input type="file" name="avatar" accept="image/*" class="sr-only" @change="pick($event)">
            </label>
            @error('avatar') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </form>
</x-admin-layout>
