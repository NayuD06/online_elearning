<x-admin-layout :title="$lesson->exists ? 'Sửa bài học' : 'Thêm bài học'">
    <a href="{{ route('instructor.courses.curriculum', $course) }}" class="mb-4 flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700"><x-icon name="chevron-left" class="h-4 w-4" /> {{ $course->title }}</a>

    <form method="POST" action="{{ $lesson->exists ? route('instructor.courses.lessons.update', [$course, $lesson]) : route('instructor.courses.lessons.store', $course) }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($lesson->exists) @method('PUT') @endif

        <div class="card min-w-0 space-y-5 p-6 xl:col-span-2">
            <div>
                <label for="title" class="form-label">Tên bài học <span class="text-rose-500">*</span></label>
                <input id="title" name="title" value="{{ old('title', $lesson->title) }}" required class="form-input">
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="content" class="form-label">Nội dung bài học</label>
                <textarea id="content" name="content" data-editor data-upload-url="{{ route('admin.editor.upload') }}" class="form-input">{{ old('content', $lesson->content) }}</textarea>
                @error('content') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="form-label flex items-center gap-1"><x-icon name="paperclip" class="h-4 w-4" /> Tài liệu đính kèm</p>
                @if ($lesson->exists && $lesson->attachments->isNotEmpty())
                    <ul class="mb-3 divide-y divide-gray-100 rounded-lg border border-gray-200">
                        @foreach ($lesson->attachments as $attachment)
                            <li class="flex items-center gap-3 px-3 py-2 text-sm">
                                <x-icon name="document" class="h-5 w-5 shrink-0 text-brand-600" />
                                <a href="{{ route('attachments.download', [$course, $lesson, $attachment]) }}" class="min-w-0 flex-1 truncate hover:text-brand-700">{{ $attachment->name }}</a>
                                <span class="shrink-0 text-xs text-gray-500">{{ $attachment->size_label }}</span>
                                {{-- The delete form lives outside this form: HTML forms cannot be nested --}}
                                <button form="delete-attachment-{{ $attachment->id }}" class="btn-ghost btn-sm text-rose-600" title="Xoá tài liệu"><x-icon name="trash" class="h-4 w-4" /></button>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <input type="file" name="attachments[]" multiple accept="{{ collect(\App\Models\LessonAttachment::EXTENSIONS)->map(fn ($extension) => '.'.$extension)->implode(',') }}"
                       class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                <p class="mt-1 text-xs text-gray-500">PDF, Word, PowerPoint, Excel, TXT hoặc ZIP; tối đa 10 tệp, mỗi tệp không quá 10 MB. Chỉ học viên đã ghi danh mới tải được.</p>
                @error('attachments') <p class="form-error">{{ $message }}</p> @enderror
                @foreach ($errors->get('attachments.*') as $messages)
                    <p class="form-error">{{ $messages[0] }}</p>
                @endforeach
            </div>
        </div>

        <div class="card h-fit space-y-5 p-6">
            <div>
                <label for="section_id" class="form-label">Chương <span class="text-rose-500">*</span></label>
                <select id="section_id" name="section_id" required class="form-input">
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected((int) old('section_id', $lesson->section_id) === $section->id)>{{ $section->title }}</option>
                    @endforeach
                </select>
                @error('section_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="video_url" class="form-label">Đường dẫn video (YouTube)</label>
                <input id="video_url" type="url" name="video_url" value="{{ old('video_url', $lesson->video_url) }}" placeholder="https://www.youtube.com/watch?v=..." class="form-input">
                @error('video_url') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="duration_minutes" class="form-label">Thời lượng (phút)</label>
                    <input id="duration_minutes" type="number" min="0" name="duration_minutes" value="{{ old('duration_minutes', $lesson->duration_minutes) }}" class="form-input">
                    @error('duration_minutes') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="position" class="form-label">Thứ tự</label>
                    <input id="position" type="number" min="0" name="position" value="{{ old('position', $lesson->position) }}" class="form-input">
                    @error('position') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_preview" value="1" @checked(old('is_preview', $lesson->is_preview)) class="rounded text-brand-600 focus:ring-brand-500">
                Cho phép học thử (không cần ghi danh)
            </label>
            <div class="flex gap-2">
                <button class="btn-primary flex-1">{{ $lesson->exists ? 'Lưu thay đổi' : 'Tạo bài học' }}</button>
                <a href="{{ route('instructor.courses.curriculum', $course) }}" class="btn-secondary">Huỷ</a>
            </div>
        </div>
    </form>

    @if ($lesson->exists)
        @foreach ($lesson->attachments as $attachment)
            <form id="delete-attachment-{{ $attachment->id }}" method="POST" action="{{ route('instructor.courses.lessons.attachments.destroy', [$course, $lesson, $attachment]) }}" onsubmit="return confirm('Xoá tài liệu này?')" class="hidden">
                @csrf @method('DELETE')
            </form>
        @endforeach
    @endif
</x-admin-layout>
