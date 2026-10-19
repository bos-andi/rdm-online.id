<?php

namespace App\Http\Middleware;

use App\Services\AffiliateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureAffiliateReferral
{
    public function __construct(private AffiliateService $affiliates) {}

    public function handle(Request $request, Closure $next): Response
    {
        $affiliate = $request->isMethod('GET') && $request->filled('ref')
            ? $this->affiliates->captureReferral($request)
            : null;

        $response = $next($request);

        if ($affiliate) {
            $response->headers->setCookie(cookie(
                AffiliateService::COOKIE,
                $affiliate->code,
                $this->affiliates->settings()['cookie_days'] * 24 * 60,
            ));
        }

        return $response;
    }
}
