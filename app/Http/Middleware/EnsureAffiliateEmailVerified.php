<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAffiliateEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user('affiliate')?->hasVerifiedEmail()) {
            return redirect()->route('affiliate.verification');
        }

        return $next($request);
    }
}
