<!DOCTYPE html>
<html lang="es"><head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#153b35">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/js/app.ts'])
    @inertiaHead
</head><body>@inertia</body></html>
