<x-app-layout title="Chứng chỉ của tôi">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900">Chứng chỉ của tôi</h1>
        <p class="mt-1 text-sm text-gray-600">Học xong toàn bộ bài học của một khoá, bạn sẽ nhận được chứng chỉ có mã xác minh.</p>

        @if ($certificates->isEmpty())
            <div class="card mt-6">
                <x-empty-state icon="trophy" title="Bạn chưa có chứng chỉ nào">
                    Hoàn thành một khoá học để nhận chứng chỉ đầu tiên. <a href="{{ route('dashboard') }}" class="font-semibold text-brand-700">Về Góc học tập</a>
                </x-empty-state>
            </div>
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                @foreach ($certificates as $certificate)
                    <div class="card flex flex-col p-5">
                        <div class="flex items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                                <x-icon name="trophy" class="h-6 w-6" />
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900">{{ $certificate->course_title }}</p>
                                <p class="text-sm text-gray-500">Cấp ngày {{ $certificate->issued_at->format('d/m/Y') }}</p>
                                <p class="mt-1 font-mono text-xs text-gray-500">{{ $certificate->code }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('certificates.download', $certificate) }}" class="btn-primary btn-sm"><x-icon name="download" class="h-4 w-4" /> Tải PDF</a>
                            <a href="{{ route('certificates.show', $certificate) }}" class="btn-secondary btn-sm">Xem và chia sẻ</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
