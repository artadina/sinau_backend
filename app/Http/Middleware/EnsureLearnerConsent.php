<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLearnerConsent
{
    // AB-11: learning data and material are available only after consent.
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->consent_given) {
            return response()->json([
                'message' => 'Anda perlu menyetujui penggunaan data sebelum mulai belajar.',
                'code' => 'consent_required',
            ], 403);
        }

        return $next($request);
    }
}
