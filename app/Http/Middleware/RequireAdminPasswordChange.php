<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminPasswordChange
{
    // FR-A12, FR-S06, AC-08
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->is_temp_password) {
            return redirect()->route('password.temporary.edit');
        }

        return $next($request);
    }
}
