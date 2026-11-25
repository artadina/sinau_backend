<?php

namespace App\Http\Controllers\Web;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // PS-29: each role can read only its own in-panel notifications.
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
            'layout' => $request->user()->role === Role::Superadmin->value
                ? 'layouts.superadmin'
                : 'layouts.admin',
        ]);
    }

    // PS-29: marking a notification read is scoped to the authenticated recipient.
    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        $moduleId = $item->data['module_id'] ?? null;

        if ($moduleId !== null && Module::query()->whereKey($moduleId)->exists()) {
            return redirect()->route(
                $request->user()->role === Role::Superadmin->value
                    ? 'superadmin.modules.show'
                    : 'admin.modules.show',
                $moduleId,
            );
        }

        return redirect()->route($request->user()->role === Role::Superadmin->value
            ? 'superadmin.notifications.index'
            : 'admin.notifications.index');
    }
}
