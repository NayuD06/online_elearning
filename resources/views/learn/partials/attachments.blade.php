<div id="tai-lieu" class="card p-6">
    <h2 class="flex items-center gap-2 text-lg font-bold text-gray-900"><x-icon name="paperclip" class="h-5 w-5 text-brand-600" /> Tài liệu bài học</h2>
    <ul class="mt-4 divide-y divide-gray-100 rounded-xl border border-gray-100">
        @foreach ($attachments as $attachment)
            <li class="flex items-center gap-3 px-4 py-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold uppercase text-brand-700">{{ $attachment->extension }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-900">{{ $attachment->name }}</p>
                    <p class="text-xs text-gray-500">{{ $attachment->size_label }}</p>
                </div>
                @if ($canLearn)
                    <a href="{{ route('attachments.download', [$course, $lesson, $attachment]) }}" class="btn-secondary btn-sm shrink-0"><x-icon name="download" class="h-4 w-4" /> Tải về</a>
                @else
                    <span class="flex shrink-0 items-center gap-1 text-xs text-gray-400"><x-icon name="lock" class="h-4 w-4" /> Ghi danh để tải</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
