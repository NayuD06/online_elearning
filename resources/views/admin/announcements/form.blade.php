<x-admin-layout :title="$announcement->exists ? 'Sửa thông báo' : 'Viết thông báo'">
    <form method="POST" enctype="multipart/form-data" action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($announcement->exists) @method('PUT') @endif

        <div class="card min-w-0 space-y-5 p-6 xl:col-span-2">
            <div>
                <label for="title" class="form-label">Tiêu đề <span class="text-rose-500">*</span></label>
                <input id="title" name="title" value="{{ old('title', $announcement->title) }}" required class="form-input">
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="content" class="form-label">Nội dung <span class="text-rose-500">*</span></label>
                <textarea id="content" name="content" data-editor data-upload-url="{{ route('admin.editor.upload') }}" class="form-input">{{ old('content', $announcement->content) }}</textarea>
                @error('content') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="space-y-6">
            <div class="card space-y-5 p-6">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $announcement->is_published)) class="rounded text-brand-600 focus:ring-brand-500">
                    Hiển thị công khai
                </label>
                <div>
                    <label for="published_at" class="form-label">Ngày đăng</label>
                    <input id="published_at" type="datetime-local" name="published_at" value="{{ old('published_at', $announcement->published_at?->format('Y-m-d\TH:i')) }}" class="form-input">
                    @error('published_at') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="card p-6" x-data="imagePreview">
                <h2 class="font-semibold text-gray-900">Ảnh bìa</h2>
                <template x-if="previews.length">
                    <img :src="previews[0]" alt="" class="mt-3 aspect-video w-full rounded-lg object-cover">
                </template>
                @if ($announcement->image_url)
                    <img x-show="! previews.length" src="{{ $announcement->image_url }}" alt="" class="mt-3 aspect-video w-full rounded-lg object-cover">
                @endif
                <input type="file" name="image" accept="image/*" @change="pick($event)" class="mt-3 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                @error('image') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="card flex gap-2 p-6">
                <button class="btn-primary flex-1">{{ $announcement->exists ? 'Lưu thay đổi' : 'Đăng thông báo' }}</button>
                <a href="{{ route('admin.announcements.index') }}" class="btn-secondary">Huỷ</a>
            </div>
        </div>
    </form>
</x-admin-layout>
