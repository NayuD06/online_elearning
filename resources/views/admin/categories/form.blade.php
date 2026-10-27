<x-admin-layout :title="$category->exists ? 'Sửa danh mục' : 'Thêm danh mục'">
    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="card max-w-2xl space-y-5 p-6">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div>
            <label for="name" class="form-label">Tên danh mục <span class="text-rose-500">*</span></label>
            <input id="name" name="name" value="{{ old('name', $category->name) }}" required class="form-input">
            @error('name') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="form-label">Mô tả</label>
            <textarea id="description" name="description" rows="4" class="form-input">{{ old('description', $category->description) }}</textarea>
            @error('description') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-2">
            <button class="btn-primary">{{ $category->exists ? 'Lưu thay đổi' : 'Tạo danh mục' }}</button>
            <a href="{{ route('admin.categories.index') }}" class="btn-secondary">Quay lại</a>
        </div>
    </form>
</x-admin-layout>
