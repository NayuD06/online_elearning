@php
    $siteName = config('app.name', 'E-Learning');
    $pageTitle = isset($title) && $title ? $title.' | '.$siteName : $siteName;
    $metaDescription = Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($description ?? '')))), 160)
        ?: 'Học trực tuyến mọi lúc, mọi nơi với các khoá học có lộ trình rõ ràng, bài kiểm tra, hỏi đáp cùng giảng viên và chứng chỉ khi hoàn thành.';
    $metaImage = ($image ?? null) ?: asset('images/og-default.png');
    // Private pages (learning, back office, account) should not show up on Google.
    $noindex = ($noindex ?? false) || request()->routeIs(
        'admin.*', 'instructor.*', 'dashboard', 'learn.*', 'quizzes.*', 'profile.*', 'wishlist.*',
        'notifications.*', 'payments.*', 'orders.*', 'certificates.index', 'login', 'register', 'password.*', 'verification.*',
    );
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<meta name="theme-color" content="#0f766e">
@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@else
    <link rel="canonical" href="{{ url()->current() }}{{ request()->integer('page') > 1 ? '?page='.request()->integer('page') : '' }}">
@endif

{{-- Open Graph / Twitter: the preview card when a link is shared on Facebook, Zalo, Messenger... --}}
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="vi_VN">
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:title" content="{{ $title ?? $siteName }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $metaImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title ?? $siteName }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $metaImage }}">

<link rel="icon" href="{{ asset('favicon.ico') }}">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,500,600,700&display=swap" rel="stylesheet" />

@stack('head')

@vite(['resources/css/app.css', 'resources/js/app.js'])
