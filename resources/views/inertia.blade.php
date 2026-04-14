<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/inertia/app.js'])
    @inertiaHead
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <a href="#main-content" class="ui-skip-link">Skip to content</a>
    @inertia
</body>
</html>
