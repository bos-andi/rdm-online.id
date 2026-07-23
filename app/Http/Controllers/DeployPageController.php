<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\SubscriptionService;
use App\Services\XenditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeployPageController extends Controller
{
    public function index(
        Request $request,
        SubscriptionService $subscriptions,
        PaymentService $payments,
        XenditService $xendit,
    ): View|RedirectResponse {
        $user = auth()->user();

        if ($user) {
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
        } else {
            $failedRetryable = collect();
        }

        $resumeInstance = null;

        if ($user && $request->filled('resume')) {
            $resumeInstance = $user->rdmInstances()
                ->where('id', $request->integer('resume'))
                ->where('status', 'pending_payment')
                ->first();

            if ($resumeInstance && ! $resumeInstance->canResumePayment()) {
                $resumeInstance = null;
            }
        }

        $xendit->availableChannels();

        $pricing = app(\App\Services\PackagePricingService::class);

        return view('deploy.index', [
            'user' => $user,
            'isGuest' => ! $user,
            'failedRetryable' => $failedRetryable,
            'resumeInstance' => $resumeInstance,
            'paymentMethods' => $payments->groupedPaymentMethods(),
            'adminFees' => $payments->adminFeesMap(),
            'packagePrices' => $pricing->prices(),
            'pricingDisplay' => $pricing->display(),
        ]);
    }
}
