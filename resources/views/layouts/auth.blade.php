<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} - Sinau</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="auth-glow pointer-events-none absolute -right-36 -top-32 h-[32rem] w-[32rem] rounded-full"></div>
        <div class="auth-glow pointer-events-none absolute -bottom-48 -left-36 h-[34rem] w-[34rem] rounded-full"></div>
        <div class="relative z-10 w-full max-w-md">
            {{ $slot }}
        </div>
    </main>
</body>
</html>
