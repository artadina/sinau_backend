<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    // FR-S05, AB-13, AC-07, AC-09, AC-10
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles, true)) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Akses tidak diizinkan untuk akun ini.',
                    'code' => 'role_not_allowed',
                ], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
