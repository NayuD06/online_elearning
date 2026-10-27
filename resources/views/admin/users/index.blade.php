<x-admin-layout title="Người dùng">
    <div class="card">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <x-search-bar placeholder="Tìm theo tên hoặc email..." class="flex-1">
                <select name="role" class="form-input mt-0 sm:w-44">
                    <option value="">Mọi vai trò</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
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
                            <td><span class="badge {{ $user->role->badgeClasses() }}">{{ $user->role->label() }}</span></td>
                            <td class="text-center">{{ $user->enrollments_count }}</td>
                            <td class="text-center">{{ $user->taught_courses_count }}</td>
                            <td class="whitespace-nowrap">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost btn-sm" title="Sửa"><x-icon name="pencil" class="h-4 w-4" /></a>
                                @unless ($user->is(Auth::user()))
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
