<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEditableModule
{
    // PS-30: submitted, approved, published, and archived content cannot be edited in place.
    public function handle(Request $request, Closure $next): Response
    {
        $module = $request->route('module');

        if ($module === null && $request->route('unit') !== null) {
            $module = $request->route('unit')->module;
        }

        abort_unless(
            $module !== null && in_array($module->status, ['draft', 'revision_required'], true),
            403,
        );

        return $next($request);
    }
}
