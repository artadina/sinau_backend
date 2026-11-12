{{-- FR-A06, AC-38 --}}
@component('layouts.admin', ['title' => $title])
    <section class="space-y-6">
        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <form method="GET" action="{{ route('admin.learners.index') }}" class="grid gap-4 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Cari nama atau email</label>
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100" placeholder="Masukkan nama atau email">
                </div>
                <div>
                    <label for="date_from" class="mb-1.5 block text-sm font-semibold text-slate-700">Tanggal daftar mulai</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100">
                </div>
                <div>
                    <label for="date_to" class="mb-1.5 block text-sm font-semibold text-slate-700">Tanggal daftar sampai</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100">
                </div>
                @foreach ($errors->all() as $error)
                    <p class="text-sm text-rose-700 md:col-span-4">{{ $error }}</p>
                @endforeach
                <div class="flex gap-2 md:col-span-4">
                    <button type="submit" class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Terapkan</button>
                    <a href="{{ route('admin.learners.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Atur ulang</a>
                </div>
            </form>
        </div>

        <div class="glass-card overflow-hidden rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-maroon-50 text-xs uppercase tracking-wide text-maroon-900">
                        <tr>
                            <th scope="col" class="px-5 py-4">Nama</th>
                            <th scope="col" class="px-5 py-4">Email</th>
                            <th scope="col" class="px-5 py-4">Nama usaha</th>
                            <th scope="col" class="px-5 py-4">Tanggal daftar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($learners as $learner)
                            <tr class="bg-white">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $learner->name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $learner->email }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $learner->business_name ?: '-' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $learner->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-slate-500">Learner tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $learners->links() }}
            </div>
        </div>
    </section>
@endcomponent
