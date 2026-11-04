<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak - Sinau</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center px-4">
    <main class="glass-card w-full max-w-lg rounded-3xl p-8 text-center sm:p-10">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-maroon-800">403</p>
        <h1 class="mt-3 text-2xl font-bold text-slate-900">Akses ditolak</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">Maaf, akun Anda tidak memiliki izin untuk membuka halaman ini.</p>
        <a href="{{ route('login') }}" class="mt-7 inline-flex items-center gap-2 rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">
            Kembali
            <i data-lucide="arrow-right" class="h-4 w-4"></i>
        </a>
    </main>
</body>
</html>
