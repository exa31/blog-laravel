<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <title inertia>{{ config('app.name', 'EBlog') }} — Modern Engineering & Tech Publication</title>
    
    <!-- Meta SEO -->
    <meta name="description" content="A modern publication for software engineering, system architecture, cloud-native tech, and digital craft." />
    <meta name="keywords" content="engineering, laravel, vuejs, system architecture, software development, cloud, devops, programming" />
    <meta name="author" content="E-Blog Editorial Team" />
    <meta name="robots" content="index, follow" />
    <meta name="theme-color" content="#0B0F19" />

    <!-- Open Graph & Social Cards -->
    <meta property="og:site_name" content="E-Blog" />
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="en_US" />
    <meta name="twitter:card" content="summary_large_image" />

    <!-- Custom Icons & Manifest -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="alternate icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" href="/favicon.svg" />
    <link rel="manifest" href="/site.webmanifest" />

    <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-[#0B0F19] dark:text-slate-100 selection:bg-amber-500 selection:text-white font-sans transition-colors duration-200">
    @inertia
</body>

</html>
