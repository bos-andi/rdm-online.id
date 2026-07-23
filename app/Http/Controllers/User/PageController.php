<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\RdmInstance;
use App\Services\DeployService;
use App\Services\UserDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private UserDashboardService $dashboardService,
    ) {}

    public function dashboard(): View
    {
        $user = auth()->user();
        $stats = $this->dashboardService->stats($user);

        return view('user.dashboard', [
            'stats' => $stats,
            'storageLabel' => $this->dashboardService->formatStorage($stats['storageBytes']),
        ]);
    }

    public function rdm(): View
    {
        return view('user.rdm.index', [
            'instances' => auth()->user()->rdmInstances()->latest()->get(),
            'subscriptions' => app(\App\Services\SubscriptionService::class),
        ]);
    }

    public function retryDeploy(RdmInstance $rdmInstance, DeployService $deployService): RedirectResponse
    {
        if ($rdmInstance->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $deployService->retryDeploy($rdmInstance);
        } catch (\Throwable $e) {
            return back()->withErrors(['rdm' => $e->getMessage()]);
        }

        return redirect()
            ->route('deploy', ['monitor' => $rdmInstance->id])
            ->with('status', 'Deploy dijalankan ulang. Pembayaran Anda tetap berlaku.');
    }

    public function domain(): View
    {
        return view('user.domain.index', [
            'instances' => auth()->user()
                ->rdmInstances()
                ->where('status', 'active')
                ->latest()
                ->get(),
        ]);
    }

    public function backup(): View
    {
        return view('user.backup.index', [
            'instances' => auth()->user()->rdmInstances()->where('status', 'active')->latest()->get(),
        ]);
    }

    public function monitoring(): View
    {
        $instances = auth()->user()->rdmInstances()->where('status', 'active')->get();

        return view('user.monitoring.index', [
            'instances' => $instances,
            'activeCount' => $instances->count(),
        ]);
    }

    public function billing(): View
    {
        return view('user.billing.index', [
            'instances' => auth()->user()->rdmInstances()->latest()->get(),
            'subscriptions' => app(\App\Services\SubscriptionService::class),
        ]);
    }

    public function support(): View
    {
        return view('user.support.index', [
            'announcements' => Announcement::query()
                ->where('is_published', true)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function profile(): View
    {
        return view('user.profile.index', [
            'user' => auth()->user(),
        ]);
    }

    public function subscriptionExpired(): View
    {
        $user = auth()->user();
        $subscriptions = app(\App\Services\SubscriptionService::class);

        return view('user.subscription.expired', [
            'instances' => $user->rdmInstances()->whereIn('status', ['active', 'expired', 'suspended'])->latest()->get(),
            'subscriptions' => $subscriptions,
        ]);
    }
}
