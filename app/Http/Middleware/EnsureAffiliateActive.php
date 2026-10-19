<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAffiliateActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user('affiliate')?->isActive()) {
            return redirect()->route('affiliate.dashboard');
        }

        return $next($request);
    }
}
