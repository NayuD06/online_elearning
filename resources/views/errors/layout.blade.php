{{-- Standalone layout: error pages may render before the session or the signed-in user is available --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') | {{ config('app.name', 'E-Learning') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-slate-50 font-sans text-gray-800 antialiased">
        <div class="flex min-h-screen flex-col">
            <header class="bg-brand-700 text-white shadow-md">
                <div class="mx-auto flex h-16 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}" class="flex items-center gap-2">
                        <x-application-logo class="h-9 w-9" />
                        <span class="text-base font-bold tracking-wide">UTH E-LEARNING</span>
                    </a>
                </div>
            </header>

            <main class="flex flex-1 items-center justify-center px-4 py-16">
                <div class="max-w-lg text-center">
                    <p class="text-7xl font-extrabold text-brand-600 sm:text-8xl">@yield('code')</p>
                    <h1 class="mt-4 text-2xl font-bold text-gray-900 sm:text-3xl">@yield('title')</h1>
                    <p class="mt-3 text-gray-600">@yield('message')</p>
                    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="{{ url('/') }}" class="btn-primary"><x-icon name="home" class="h-4 w-4" /> Về trang chủ</a>
                        @hasSection('actions')
                            @yield('actions')
                        @else
                            <a href="{{ url('/khoa-hoc') }}" class="btn-secondary"><x-icon name="book" class="h-4 w-4" /> Xem khoá học</a>
                        @endif
                    </div>
                </div>
            </main>

            <footer class="py-6 text-center text-sm text-gray-500">© {{ now()->year }} UTH E-Learning</footer>
        </div>
    </body>
</html>
