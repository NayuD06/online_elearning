<x-admin-layout title="Danh mục">
    <div class="card">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <x-search-bar placeholder="Tìm theo tên danh mục..." class="flex-1" />
            <a href="{{ route('admin.categories.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm danh mục</a>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Tên danh mục</th><th>Mô tả</th><th class="text-center">Số khoá học</th><th class="text-right">Thao tác</th></tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="font-medium text-gray-900">{{ $category->name }}</td>
                            <td class="max-w-md truncate text-gray-500">{{ $category->description ?: '—' }}</td>
                            <td class="text-center"><span class="badge bg-brand-50 text-brand-700">{{ $category->courses_count }}</span></td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn-ghost btn-sm" title="Sửa"><x-icon name="pencil" class="h-4 w-4" /></a>
                                <x-delete-button :action="route('admin.categories.destroy', $category)" confirm="Xoá danh mục này? Các khoá học thuộc danh mục sẽ không bị xoá." />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty-state icon="tag" title="Chưa có danh mục" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $categories->links() }}</div>
    </div>
</x-admin-layout>
