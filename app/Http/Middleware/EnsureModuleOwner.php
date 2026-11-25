<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleOwner
{
    // FR-A02, PS-30: Admin mutations are limited to modules created by that Admin.
    public function handle(Request $request, Closure $next): Response
    {
        $module = $request->route('module');

        if ($module === null && $request->route('unit') !== null) {
            $module = $request->route('unit')->module;
        }

        abort_unless(
            $request->user()?->role === Role::Admin->value
                && $module !== null
                && $module->created_by === $request->user()->id,
            404,
        );

        return $next($request);
    }
}
