@component($layout, ['title' => 'Notifikasi'])
    <section class="mx-auto max-w-4xl space-y-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Notifikasi</h1>
            <p class="mt-1 text-sm text-slate-600">Pembaruan pengajuan, keputusan review, dan periode aktif modul.</p>
        </div>

        <div class="glass-card divide-y divide-slate-100 overflow-hidden rounded-2xl">
            @forelse ($notifications as $notification)
                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between {{ $notification->read_at ? 'bg-white' : 'bg-maroon-50/50' }}">
                    <div>
                        <p class="text-sm {{ $notification->read_at ? 'text-slate-700' : 'font-semibold text-slate-900' }}">
                            {{ $notification->data['message'] ?? 'Ada pembaruan modul.' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->format('d M Y H:i') }}</p>
                    </div>
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route(request()->routeIs('superadmin.*') ? 'superadmin.notifications.read' : 'admin.notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-maroon-200 px-3 py-1.5 text-xs font-semibold text-maroon-800 hover:bg-maroon-50">
                                Buka dan tandai dibaca
                            </button>
                        </form>
                    @elseif (! empty($notification->data['module_id']))
                        <a href="{{ route(request()->routeIs('superadmin.*') ? 'superadmin.modules.show' : 'admin.modules.show', $notification->data['module_id']) }}" class="text-xs font-semibold text-maroon-800 hover:text-maroon-900">Buka modul</a>
                    @endif
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-600">Belum ada notifikasi.</p>
            @endforelse
        </div>
        {{ $notifications->links() }}
    </section>
@endcomponent
