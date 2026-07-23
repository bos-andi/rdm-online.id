<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(
        private SubscriptionService $subscriptions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $this->subscriptions->syncExpiredInstancesForUser($user);

        if ($this->subscriptions->userHasActiveSubscription($user)) {
            return $next($request);
        }

        if ($user->rdmInstances()->whereIn('status', ['active', 'expired', 'suspended'])->doesntExist()) {
            return $next($request);
        }

        $allowedRoutes = [
            'user.billing',
            'user.profile',
            'user.profile.password',
            'user.profile.password.dismiss',
            'user.support',
            'user.subscription.expired',
        ];

        if ($request->routeIs($allowedRoutes)) {
            return $next($request);
        }

        return redirect()->route('user.subscription.expired');
    }
}
