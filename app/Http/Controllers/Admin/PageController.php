<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\VpsServer;
use App\Services\AdminPanelService;
use App\Services\PaymentService;
use App\Services\VpsSpecService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private AdminPanelService $adminPanel,
    ) {}

    public function dashboard(): View
    {
        return view('admin.dashboard', $this->adminPanel->dashboardStats());
    }

    public function pelanggan(): View
    {
        return view('admin.pelanggan.index', [
            'customers' => \App\Models\User::query()
                ->withCount('rdmInstances')
                ->latest()
                ->get(),
            'instances' => \App\Models\RdmInstance::query()->latest()->get(),
        ]);
    }

    public function rdm(): View
    {
        $instances = \App\Models\RdmInstance::query()->latest()->get();

        return view('admin.rdm.index', [
            'instances' => $instances,
            'total' => $instances->where('status', 'active')->count(),
            'subscriptions' => app(\App\Services\SubscriptionService::class),
        ]);
    }

    public function vps(VpsSpecService $specs): View
    {
        VpsServer::query()->where('is_local', true)->each(
            fn (VpsServer $server) => $specs->detectAndStore($server)
        );

        return view('admin.vps.index', [
            'servers' => $this->adminPanel->vpsServers(),
            'activeServer' => VpsServer::activeForDeploy(),
        ]);
    }

    public function dns(): View
    {
        return view('admin.dns.index', $this->adminPanel->dnsOverview());
    }

    public function database(): View
    {
        return view('admin.database.index', $this->adminPanel->databaseOverview());
    }

    public function backup(): View
    {
        return view('admin.backup.index', [
            'instances' => $this->adminPanel->backupOverview(),
        ]);
    }

    public function billing(): View
    {
        return view('admin.billing.index', $this->adminPanel->billingOverview());
    }

    public function openCheckout(string $subdomain, PaymentService $payments): RedirectResponse
    {
        $instance = $payments->instanceForRenewal($subdomain)
            ?? \App\Models\RdmInstance::query()->where('subdomain', $subdomain)->firstOrFail();

        $payments->storeCheckoutSession($subdomain, $instance->pic_email);

        return redirect()->route('renewal.checkout', $subdomain);
    }

    public function support(): View
    {
        return view('admin.support.index', $this->adminPanel->supportOverview());
    }

    public function pengumuman(): View
    {
        return view('admin.pengumuman.index', [
            'announcements' => Announcement::query()->latest()->get(),
        ]);
    }

    public function settings(\App\Services\PackagePricingService $pricing): View
    {
        return view('admin.settings.index', [
            'settings' => $this->adminPanel->settingsOverview(),
            'packagePrices' => $pricing->prices(),
            'pricingDisplay' => $pricing->display(),
            'promoEndsAt' => $pricing->promoEndsAt(),
            'monthlyOriginal' => $pricing->originalPriceFor('monthly'),
            'yearlyOriginal' => $pricing->originalPriceFor('yearly'),
        ]);
    }

    public function log(): View
    {
        return view('admin.log.index', [
            'logs' => $this->adminPanel->activityLogs(),
        ]);
    }
}
