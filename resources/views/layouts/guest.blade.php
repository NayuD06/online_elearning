<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="grid min-h-screen lg:grid-cols-2">
            <div class="relative hidden overflow-hidden bg-brand-700 lg:block">
                <div class="absolute -left-24 -top-24 h-96 w-96 rounded-full bg-brand-500/40"></div>
                <div class="absolute -bottom-32 -right-16 h-[28rem] w-[28rem] rounded-full bg-brand-900/40"></div>
                <div class="relative flex h-full flex-col justify-between p-12 text-white">
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <x-application-logo class="h-11 w-11" />
                        <span class="text-xl font-bold tracking-wide">UTH E-LEARNING</span>
                    </a>

                    <div>
                        <h1 class="text-4xl font-bold leading-tight">Học tập không giới hạn,<br>tiến bộ mỗi ngày.</h1>
                        <p class="mt-4 max-w-md text-brand-100">Hàng chục khoá học chất lượng, theo dõi tiến độ trực quan và cộng đồng học viên năng động đang chờ bạn.</p>

                        <div class="mt-8 grid max-w-md grid-cols-3 gap-4">
                            @foreach ([['academic', 'Khoá học'], ['users', 'Học viên'], ['trophy', 'Chứng nhận']] as [$icon, $label])
                                <div class="rounded-xl bg-white/10 p-4 text-center backdrop-blur">
                                    <x-icon :name="$icon" class="mx-auto h-7 w-7" />
                                    <p class="mt-2 text-sm font-medium">{{ $label }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <p class="text-sm text-brand-200">© {{ now()->year }} UTH E-Learning</p>
                </div>
            </div>

            <div class="flex flex-col items-center justify-center bg-slate-50 px-4 py-10 sm:px-6">
                <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2 lg:hidden">
                    <x-application-logo class="h-10 w-10" />
                    <span class="text-lg font-bold text-brand-800">UTH E-LEARNING</span>
                </a>

                <div class="card w-full max-w-md px-6 py-8 sm:px-8">
                    {{ $slot }}
                </div>

                <a href="{{ route('home') }}" class="mt-6 flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700">
                    <x-icon name="chevron-left" class="h-4 w-4" /> Về trang chủ
                </a>
            </div>
        </div>
    </body>
</html>
