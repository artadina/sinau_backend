@component('layouts.admin', ['title' => $title])
    <section class="glass-card relative overflow-hidden rounded-3xl p-7 sm:p-10">
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-maroon-50"></div>
        <div class="relative max-w-2xl">
            <span class="inline-flex items-center gap-2 rounded-full border border-maroon-100 bg-maroon-50 px-3 py-1.5 text-xs font-semibold text-maroon-800">
                <i data-lucide="circle-help" class="h-3.5 w-3.5"></i>
                Fondasi panel
            </span>
            <h1 class="mt-5 text-2xl font-bold text-slate-900 sm:text-3xl">{{ $title }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">Halaman ini segera hadir pada langkah pengembangan berikutnya.</p>
        </div>
    </section>
@endcomponent
