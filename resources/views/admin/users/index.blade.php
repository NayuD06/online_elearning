<x-admin-layout title="Người dùng">
    @if ($pendingInstructorCount && request('status') !== 'instructor_pending')
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <span><strong>{{ $pendingInstructorCount }}</strong> người dùng đang chờ duyệt làm giảng viên.</span>
            <a href="{{ route('admin.users.index', ['status' => 'instructor_pending']) }}" class="font-medium underline">Xem danh sách</a>
        </div>
    @endif

    <div class="card">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <x-search-bar placeholder="Tìm theo tên hoặc email..." class="flex-1">
                <select name="role" class="form-input mt-0 sm:w-44">
                    <option value="">Mọi vai trò</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-input mt-0 sm:w-52">
                    <option value="">Mọi trạng thái</option>
                    <option value="instructor_pending" @selected(request('status') === 'instructor_pending')>Chờ duyệt giảng viên</option>
                    <option value="locked" @selected(request('status') === 'locked')>Đang bị khoá</option>
                </select>
            </x-search-bar>
            <a href="{{ route('admin.users.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm người dùng</a>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Người dùng</th><th>Số điện thoại</th><th>Vai trò</th><th class="text-center">Ghi danh</th><th class="text-center">Giảng dạy</th><th>Ngày tạo</th><th class="text-right">Thao tác</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->avatar_url }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->phone ?: '—' }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <span class="badge {{ $user->role->badgeClasses() }}">{{ $user->role->label() }}</span>
                                    @if ($user->hasPendingInstructorRequest())
                                        <span class="badge bg-amber-100 text-amber-700" title="Gửi lúc {{ $user->instructor_requested_at?->format('d/m/Y H:i') }}">Chờ duyệt giảng viên</span>
                                    @endif
                                    @if ($user->isLocked())
                                        <span class="badge bg-gray-800 text-white" title="Khoá lúc {{ $user->locked_at->format('d/m/Y H:i') }}">Đã khoá</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">{{ $user->enrollments_count }}</td>
                            <td class="text-center">{{ $user->taught_courses_count }}</td>
                            <td class="whitespace-nowrap">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap text-right">
                                @if ($user->hasPendingInstructorRequest())
                                    <form method="POST" action="{{ route('admin.users.instructor.approve', $user) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <button class="btn-sm btn bg-emerald-50 text-emerald-700 hover:bg-emerald-100" title="Duyệt làm giảng viên"><x-icon name="check" class="h-4 w-4" /></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.instructor.reject', $user) }}" class="inline" onsubmit="return confirm('Từ chối yêu cầu làm giảng viên của tài khoản này?')">
                                        @csrf @method('PATCH')
                                        <button class="btn-ghost btn-sm text-rose-600" title="Từ chối yêu cầu giảng viên"><x-icon name="x" class="h-4 w-4" /></button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost btn-sm" title="Sửa"><x-icon name="pencil" class="h-4 w-4" /></a>
                                @unless ($user->is(Auth::user()))
                                    <form method="POST" action="{{ route('admin.users.lock', $user) }}" class="inline"
                                          onsubmit="return confirm(@js($user->isLocked() ? 'Mở khoá tài khoản này?' : 'Khoá tài khoản này? Người dùng sẽ bị đăng xuất và không đăng nhập được nữa.'))">
                                        @csrf @method('PATCH')
                                        <button class="btn-ghost btn-sm {{ $user->isLocked() ? 'text-emerald-700' : 'text-gray-600' }}" title="{{ $user->isLocked() ? 'Mở khoá' : 'Khoá tài khoản' }}">
                                            <x-icon :name="$user->isLocked() ? 'lock-open' : 'lock'" class="h-4 w-4" />
                                        </button>
                                    </form>
                                    <x-delete-button :action="route('admin.users.destroy', $user)" confirm="Xoá tài khoản này? Toàn bộ dữ liệu liên quan sẽ bị xoá." />
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state icon="users" title="Không tìm thấy người dùng" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $users->links() }}</div>
    </div>
</x-admin-layout>
