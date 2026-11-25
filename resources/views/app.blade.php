<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f3b63">
    <meta name="base-path" content="{{ request()->getBaseUrl() }}">
    <meta name="description" content="SIGO · Sistema Integral de Gestión Organizacional">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500..800&family=Nunito+Sans:opsz,wght@6..12,400..800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <title inertia>{{ config('app.name', 'SIGO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
