@php
    $isOwner = auth()->id() === $certificate->user_id;
    $shareUrl = route('certificates.show', $certificate);
@endphp

<x-app-layout :title="'Chứng chỉ '.$certificate->course_title.' – '.$certificate->student_name"
              :description="$certificate->student_name.' đã hoàn thành khoá học '.$certificate->course_title.' trên UTH E-Learning ngày '.$certificate->issued_at->format('d/m/Y').'. Mã xác minh '.$certificate->code.'.'"
              :image="$certificate->course?->thumbnail_url">
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
            <x-icon name="check-circle" class="h-6 w-6 shrink-0" />
            <p class="text-sm"><strong>Chứng chỉ hợp lệ.</strong> Chứng chỉ này do UTH E-Learning cấp, mã <span class="font-mono font-semibold">{{ $certificate->code }}</span>.</p>
        </div>

        {{-- HTML preview of the PDF --}}
        <div class="mt-6 rounded-2xl bg-teal-50 p-3 shadow-sm sm:p-5">
            <div class="border-2 border-brand-700 p-1.5">
                <div class="border border-teal-300 bg-white px-5 py-8 text-center sm:px-12 sm:py-12">
                    <p class="text-sm font-bold tracking-[0.3em] text-brand-700">UTH E-LEARNING</p>
                    <p class="mt-6 font-serif text-3xl font-bold tracking-wider text-teal-900 sm:text-5xl">CHỨNG NHẬN</p>
                    <p class="mt-2 text-xs uppercase tracking-[0.3em] text-gray-500 sm:text-sm">Hoàn thành khoá học</p>
                    <p class="mt-8 text-sm text-gray-600">Chứng nhận học viên</p>
                    <p class="mt-2 font-serif text-2xl font-bold text-gray-900 sm:text-4xl">{{ $certificate->student_name }}</p>
                    <div class="mx-auto mt-3 w-2/3 border-t border-teal-200"></div>
                    <p class="mt-5 text-sm text-gray-600">đã hoàn thành toàn bộ nội dung khoá học</p>
                    <p class="mt-2 text-lg font-bold text-brand-700 sm:text-2xl">{{ $certificate->course_title }}</p>

                    <dl class="mt-10 grid grid-cols-2 gap-6 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500">Ngày cấp</dt>
                            <dd class="font-semibold text-gray-800">{{ $certificate->issued_at->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Giảng viên phụ trách</dt>
                            <dd class="font-semibold text-gray-800">{{ $certificate->instructor_name ?? 'UTH E-Learning' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-2">
            @if ($isOwner || auth()->user()?->isAdmin())
                <a href="{{ route('certificates.download', $certificate) }}" class="btn-primary"><x-icon name="download" class="h-5 w-5" /> Tải chứng chỉ (PDF)</a>
            @endif
            @if ($isOwner)
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="btn-secondary">Chia sẻ lên Facebook</a>
                <a href="https://www.linkedin.com/profile/add?{{ http_build_query([
                    'startTask' => 'CERTIFICATION_NAME',
                    'name' => $certificate->course_title,
                    'organizationName' => config('app.name'),
                    'issueYear' => $certificate->issued_at->year,
                    'issueMonth' => $certificate->issued_at->month,
                    'certUrl' => $shareUrl,
                    'certId' => $certificate->code,
                ]) }}" target="_blank" rel="noopener" class="btn-secondary">Thêm vào LinkedIn</a>
                <button type="button" x-data="{ copied: false }" @click="navigator.clipboard.writeText(@js($shareUrl)); copied = true; setTimeout(() => copied = false, 2000)" class="btn-ghost">
                    <span x-show="! copied">Sao chép link xác minh</span>
                    <span x-show="copied" x-cloak>Đã sao chép!</span>
                </button>
            @endif
            @if ($certificate->course?->isPublished())
                <a href="{{ route('courses.show', $certificate->course) }}" class="btn-ghost">Xem khoá học</a>
            @endif
        </div>

        <p class="mt-6 text-sm text-gray-500">
            Muốn kiểm tra một chứng chỉ khác? Vào trang <a href="{{ route('certificates.verify') }}" class="font-medium text-brand-700 hover:underline">xác minh chứng chỉ</a> và nhập mã in ở cuối chứng chỉ.
        </p>
    </div>
</x-app-layout>
