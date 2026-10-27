<x-admin-layout :title="$course->exists ? 'Sửa khoá học' : 'Thêm khoá học'">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $course->exists ? route('instructor.courses.update', $course) : route('instructor.courses.store') }}"
          class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($course->exists) @method('PUT') @endif

        <div class="min-w-0 space-y-6 xl:col-span-2">
            <div class="card space-y-5 p-6">
                <div>
                    <label for="title" class="form-label">Tên khoá học <span class="text-rose-500">*</span></label>
                    <input id="title" name="title" value="{{ old('title', $course->title) }}" required class="form-input">
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="summary" class="form-label">Mô tả ngắn <span class="text-rose-500">*</span></label>
                    <textarea id="summary" name="summary" rows="2" maxlength="500" required class="form-input">{{ old('summary', $course->summary) }}</textarea>
                    @error('summary') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="form-label">Nội dung chi tiết</label>
                    <textarea id="description" name="description" data-editor data-upload-url="{{ route('admin.editor.upload') }}" class="form-input">{{ old('description', $course->description) }}</textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Gallery: multiple image upload --}}
            <div class="card p-6" x-data="imagePreview">
                <h2 class="font-semibold text-gray-900">Thư viện ảnh</h2>
                <p class="text-sm text-gray-500">Chọn nhiều ảnh cùng lúc (tối đa 10 ảnh, mỗi ảnh ≤ 2MB).</p>

                @if ($course->exists && $course->images->isNotEmpty())
                    <p class="mt-4 text-sm font-medium text-gray-700">Ảnh hiện có (tích để xoá khi lưu)</p>
                    <div class="mt-2 grid grid-cols-3 gap-3 sm:grid-cols-5">
                        @foreach ($course->images as $image)
                            <label class="group relative cursor-pointer overflow-hidden rounded-lg border border-gray-200">
                                <img src="{{ $image->url }}" alt="" class="aspect-square w-full object-cover">
                                <span class="absolute inset-x-0 bottom-0 flex items-center gap-1 bg-gray-900/60 px-2 py-1 text-xs text-white">
                                    <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" class="rounded text-rose-600 focus:ring-rose-500"> Xoá
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <label class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 p-6 text-center transition hover:border-brand-400 hover:bg-brand-50/40">
                    <x-icon name="photo" class="h-10 w-10 text-gray-400" />
                    <span class="mt-2 text-sm font-medium text-brand-700">Bấm để chọn ảnh</span>
                    <input type="file" name="images[]" accept="image/*" multiple class="sr-only" @change="pick($event)">
                </label>
                @error('images') <p class="form-error">{{ $message }}</p> @enderror
                @error('images.*') <p class="form-error">{{ $message }}</p> @enderror

                <div x-show="previews.length" class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5">
                    <template x-for="(src, index) in previews" :key="index">
                        <img :src="src" alt="" class="aspect-square w-full rounded-lg object-cover ring-2 ring-brand-200">
                    </template>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card space-y-5 p-6">
                <div>
                    <p class="form-label">Trạng thái</p>
                    <span class="badge mt-1 {{ $course->status->badgeClasses() }}">{{ $course->status->label() }}</span>
                    @if ($course->status === App\Enums\CourseStatus::Rejected && $course->rejection_reason)
                        <p class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700"><strong>Lý do từ chối:</strong> {{ $course->rejection_reason }}</p>
                    @endif
                    <p class="mt-2 text-xs text-gray-500">Khoá học mới là bản nháp. Khi đã thêm đủ chương và bài học, hãy gửi duyệt để quản trị viên xuất bản.</p>
                </div>

                <div>
                    <label for="category_id" class="form-label">Danh mục</label>
                    <select id="category_id" name="category_id" class="form-input">
                        <option value="">— Chưa phân loại —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $course->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="level" class="form-label">Trình độ</label>
                    <select id="level" name="level" class="form-input">
                        @foreach ($levels as $level)
                            <option value="{{ $level->value }}" @selected(old('level', $course->level?->value) === $level->value)>{{ $level->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="price" class="form-label">Học phí (VNĐ)</label>
                    <input id="price" type="number" name="price" min="0" step="1000" value="{{ old('price', $course->price) }}" class="form-input">
                    <p class="mt-1 text-xs text-gray-500">Nhập 0 nếu khoá học miễn phí.</p>
                    @error('price') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="card p-6" x-data="imagePreview">
                <h2 class="font-semibold text-gray-900">Ảnh đại diện</h2>
                <div class="mt-3 overflow-hidden rounded-lg">
                    <template x-if="previews.length">
                        <img :src="previews[0]" alt="" class="aspect-video w-full object-cover">
                    </template>
                    <template x-if="! previews.length">
                        <x-course-thumbnail :course="$course" class="aspect-video w-full" />
                    </template>
                </div>
                <input type="file" name="thumbnail" accept="image/*" @change="pick($event)" class="mt-3 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                @error('thumbnail') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="card flex flex-col gap-2 p-6">
                <button class="btn-primary w-full">{{ $course->exists ? 'Lưu thay đổi' : 'Tạo khoá học' }}</button>
                @if ($course->exists)
                    <a href="{{ route('instructor.courses.curriculum', $course) }}" class="btn-secondary w-full"><x-icon name="list" class="h-4 w-4" /> Chương & bài học ({{ $course->sections_count }} chương, {{ $course->lessons_count }} bài)</a>
                @endif
                <a href="{{ route('instructor.courses.index') }}" class="btn-ghost w-full">Quay lại danh sách</a>
            </div>
        </div>
    </form>

    @if ($course->exists && $course->status->canBeSubmitted())
        <form method="POST" action="{{ route('instructor.courses.submit', $course) }}" class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-brand-200 bg-brand-50 p-4">
            @csrf
            <p class="text-sm text-brand-800">Đã sẵn sàng? Gửi khoá học để quản trị viên kiểm duyệt.</p>
            <button class="btn-primary"><x-icon name="check-circle" class="h-4 w-4" /> Gửi duyệt</button>
        </form>
    @endif
</x-admin-layout>
