<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Superadmin - Sinau</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center px-4">
    <main class="glass-card w-full max-w-lg rounded-3xl p-8 text-center sm:p-10">
        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-maroon-50 text-maroon-800">
            <i data-lucide="shield-check" class="h-6 w-6"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Panel Superadmin</h1>
        <p class="mt-3 text-sm text-slate-600">Segera hadir.</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-7">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-maroon-300 hover:text-maroon-800">
                <i data-lucide="log-out" class="h-4 w-4"></i>
                Keluar
            </button>
        </form>
    </main>
</body>
</html>
