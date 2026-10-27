<x-app-layout title="Xác minh chứng chỉ" description="Kiểm tra một chứng chỉ hoàn thành khoá học do UTH E-Learning cấp bằng mã in trên chứng chỉ.">
    <div class="mx-auto max-w-xl px-4 py-12 sm:px-6">
        <div class="card p-6 sm:p-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                <x-icon name="trophy" class="h-6 w-6" />
            </div>
            <h1 class="mt-4 text-2xl font-bold text-gray-900">Xác minh chứng chỉ</h1>
            <p class="mt-1 text-sm text-gray-600">Nhập mã in ở cuối chứng chỉ (ví dụ <span class="font-mono">UTH-7K2Q-9MXD</span>) để xem chứng chỉ có phải do UTH E-Learning cấp hay không.</p>

            <form method="GET" action="{{ route('certificates.verify') }}" class="mt-6 flex flex-col gap-3 sm:flex-row">
                <label class="flex-1">
                    <span class="sr-only">Mã chứng chỉ</span>
                    <input type="text" name="code" value="{{ $code ?? '' }}" required maxlength="30" autocomplete="off" placeholder="UTH-XXXX-XXXX" class="form-input font-mono uppercase">
                </label>
                <button class="btn-primary">Kiểm tra</button>
            </form>

            @if ($notFound ?? false)
                <p class="mt-4 flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <x-icon name="x" class="h-5 w-5 shrink-0" /> Không tìm thấy chứng chỉ có mã <span class="font-mono font-semibold">{{ $code }}</span>. Hãy kiểm tra lại mã.
                </p>
            @endif
        </div>
    </div>
</x-app-layout>
