<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RdmInstance;
use App\Services\DeployService;
use App\Services\DeployStatusService;
use App\Services\DestroyStatusService;
use App\Services\RdmDestroyService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RdmInstanceController extends Controller
{
    public function renew(RdmInstance $rdmInstance, SubscriptionService $subscriptions): RedirectResponse
    {
        try {
            $subscriptions->renewInstance($rdmInstance);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['rdm' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.rdm')
            ->with('status', 'RDM '.$rdmInstance->fqdn.' diaktifkan kembali.');
    }

    public function deactivate(Request $request, RdmInstance $rdmInstance, SubscriptionService $subscriptions): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $subscriptions->deactivateInstance($rdmInstance, $data['reason'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['rdm' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.rdm')
            ->with('status', 'RDM '.$rdmInstance->fqdn.' berhasil dinonaktifkan.');
    }

    public function retry(Request $request, RdmInstance $rdmInstance, DeployService $deployService): RedirectResponse|JsonResponse
    {
        try {
            $deployService->retryDeploy($rdmInstance);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['rdm' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Deploy dijalankan ulang.',
                'instance_id' => $rdmInstance->id,
                'fqdn' => $rdmInstance->fqdn,
            ]);
        }

        return redirect()
            ->route('admin.rdm')
            ->with('status', 'Deploy '.$rdmInstance->fqdn.' dijalankan ulang.');
    }

    public function destroy(Request $request, RdmInstance $rdmInstance, RdmDestroyService $destroyService): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'confirm_subdomain' => ['required', 'string'],
        ]);

        if (! $rdmInstance->canDestroy()) {
            $message = 'RDM sedang deploy atau penghapusan — tunggu selesai dulu.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['rdm' => $message]);
        }

        if (strtolower($data['confirm_subdomain']) !== strtolower($rdmInstance->subdomain)) {
            $message = 'Konfirmasi subdomain tidak cocok.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['rdm' => $message]);
        }

        $fqdn = $rdmInstance->fqdn;
        $instanceId = $rdmInstance->id;

        try {
            $destroyService->queueDestroy($rdmInstance);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal menghapus RDM: '.$e->getMessage()], 422);
            }

            return back()->withErrors(['rdm' => 'Gagal menghapus RDM: '.$e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Penghapusan dimulai.',
                'instance_id' => $instanceId,
                'fqdn' => $fqdn,
                'status_url' => route('admin.rdm.destroy-status', $instanceId),
            ]);
        }

        return redirect()
            ->route('admin.rdm')
            ->with('status', 'Penghapusan '.$fqdn.' sedang berjalan.');
    }

    public function destroyStatus(int $rdmInstance, DestroyStatusService $statusService): JsonResponse
    {
        $cachedFqdn = Cache::get(app(RdmDestroyService::class)->destroyedCacheKey($rdmInstance));

        if ($cachedFqdn) {
            return response()->json($statusService->destroyedPayload($cachedFqdn));
        }

        $instance = RdmInstance::query()->find($rdmInstance);

        if (! $instance) {
            return response()->json(['message' => 'Instance tidak ditemukan.'], 404);
        }

        if ($instance->status === 'destroying') {
            return response()->json($statusService->payload($instance));
        }

        $destroySteps = collect(config('deploy.destroy_steps'))->pluck('key')->all();

        if ($instance->status === 'failed' && in_array($instance->current_step, $destroySteps, true)) {
            return response()->json($statusService->payload($instance));
        }

        return response()->json(['message' => 'Instance tidak dalam proses penghapusan.'], 422);
    }

    public function deployStatus(RdmInstance $rdmInstance, DeployStatusService $statusService): JsonResponse
    {
        return response()->json($statusService->payload($rdmInstance));
    }
}
