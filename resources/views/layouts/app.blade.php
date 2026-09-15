<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $siteSetting = \App\Models\ElectionSetting::current();
        $pageTitle = trim($__env->yieldContent('title', 'Bilik Suara Siswa - ' . ($siteSetting->school_name ?? 'Pilketos')));
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="Portal E-Voting Pilketos {{ $siteSetting->school_name ?? '' }} - {{ $siteSetting->election_title ?? 'Pemilihan Ketua OSIS' }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="Portal E-Voting Pilketos {{ $siteSetting->school_name ?? '' }} - {{ $siteSetting->election_title ?? 'Pemilihan Ketua OSIS' }}">
    @if(!empty($siteSetting->school_logo))
        <meta property="og:image" content="{{ asset('storage/' . $siteSetting->school_logo) }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="icon" href="{{ $siteSetting->getFaviconUrl() }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased selection:bg-indigo-500 selection:text-white">
    <main class="flex-grow flex flex-col">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
