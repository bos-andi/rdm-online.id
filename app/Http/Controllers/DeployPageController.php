<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PackagePricingService;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeployPageController extends Controller
{
    public function index(Request $request, PaymentService $payments): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('user.deploy', $request->query());
        }

        return view('deploy.index', [
            'user' => null,
            'isGuest' => true,
            'failedRetryable' => collect(),
            'resumeInstance' => null,
            ...$this->wizardData($payments),
        ]);
    }

    public function panel(
        Request $request,
        SubscriptionService $subscriptions,
        PaymentService $payments,
    ): View|RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $subscriptions->syncExpiredInstancesForUser($user);

        $hasPaidInstances = $user->rdmInstances()
            ->whereIn('status', ['active', 'expired', 'suspended'])
            ->exists();

        if ($hasPaidInstances && ! $subscriptions->userHasActiveSubscription($user)) {
            return redirect()->route('user.subscription.expired');
        }

        $failedRetryable = $user->rdmInstances()
            ->where('status', 'failed')
            ->whereHas('payments', fn ($q) => $q->where('status', 'paid')->where('meta->purpose', 'deploy'))
            ->latest()
            ->get();

        $resumeInstance = null;

        if ($request->filled('resume')) {
            $resumeInstance = $user->rdmInstances()
                ->where('id', $request->integer('resume'))
                ->where('status', 'pending_payment')
                ->first();

            if ($resumeInstance && ! $resumeInstance->canResumePayment()) {
                $resumeInstance = null;
            }
        }

        return view('user.deploy.create', [
            'user' => $user,
            'isGuest' => false,
            'failedRetryable' => $failedRetryable,
            'resumeInstance' => $resumeInstance,
            ...$this->wizardData($payments),
        ]);
    }

    private function wizardData(PaymentService $payments): array
    {
        $pricing = app(PackagePricingService::class);

        return [
            'paymentMethods' => $payments->groupedPaymentMethods(),
            'adminFees' => $payments->adminFeesMap(),
            'packagePrices' => $pricing->prices(),
            'pricingDisplay' => $pricing->display(),
        ];
    }
}
