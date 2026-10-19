<?php

namespace App\Services;

use App\Jobs\SendDeployPaymentReminderJob;
use App\Mail\DeployPreparedMail;
use App\Models\RdmInstance;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DeployPrepareService
{
    public function __construct(
        private DeployService $deployService,
        private SubdomainService $subdomainService,
        private SubscriptionService $subscriptions,
        private PendingPaymentService $pendingPayments,
    ) {}

    /**
     * @return array{user: User, instance: RdmInstance, account_created: bool, instance_created: bool}
     */
    public function prepare(array $data, ?User $user, bool $isGuest): array
    {
        if ($user) {
            $this->subscriptions->syncExpiredInstancesForUser($user);
            $this->assertUserCanDeploy($user);
        }

        $accountCreated = false;

        if ($isGuest) {
            $user = User::create([
                'name' => $data['pic_name'],
                'madrasah_name' => $data['madrasah_name'],
                'jenjang' => $data['jenjang'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'must_change_password' => false,
            ]);

            app(AffiliateService::class)->attachToUser(
                $user,
                app(AffiliateService::class)->resolveForSignup($data['referral_code'] ?? null, request()),
            );

            Auth::login($user);
            request()->session()->regenerate();
            $accountCreated = true;
        } else {
            $user->fill([
                'madrasah_name' => $data['madrasah_name'],
                'jenjang' => $data['jenjang'],
            ])->save();
        }

        $data['pic_email'] = $user->email;

        $error = $this->subdomainService->validate($data['subdomain']);
        if ($error) {
            throw new \InvalidArgumentException($error);
        }

        $normalizedSubdomain = $this->subdomainService->normalize($data['subdomain']);

        return DB::transaction(function () use ($user, $data, $normalizedSubdomain, $accountCreated) {
            $otherPending = $user->rdmInstances()
                ->where('status', 'pending_payment')
                ->where('subdomain', '!=', $normalizedSubdomain)
                ->get();

            foreach ($otherPending as $stale) {
                $this->pendingPayments->deletePendingInstance($stale);
            }

            $pendingInstance = RdmInstance::query()
                ->where('subdomain', $normalizedSubdomain)
                ->where('status', 'pending_payment')
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $instanceCreated = false;
            $ttlHours = (int) config('deploy.pending_payment_ttl_hours', 24);

            if ($pendingInstance) {
                $pendingInstance->update([
                    'madrasah_name' => $data['madrasah_name'],
                    'jenjang' => $data['jenjang'],
                    'pic_email' => $data['pic_email'],
                    'package' => $data['package'],
                    'pending_payment_expires_at' => now()->addHours($ttlHours),
                ]);
            } else {
                if (! $this->subdomainService->isAvailable($data['subdomain'])) {
                    throw new \InvalidArgumentException('Subdomain tidak tersedia.');
                }

                $pendingInstance = $this->deployService->createInstance($data, $user->id, 'pending_payment');
                $pendingInstance->update([
                    'pending_payment_expires_at' => now()->addHours($ttlHours),
                ]);
                $instanceCreated = true;
            }

            if ($instanceCreated || $accountCreated) {
                $this->sendWelcomeIfNeeded($pendingInstance, $user, $accountCreated);
            }

            if ($instanceCreated) {
                $this->schedulePaymentReminder($pendingInstance);
            }

            return [
                'user' => $user->fresh(),
                'instance' => $pendingInstance->fresh(),
                'account_created' => $accountCreated,
                'instance_created' => $instanceCreated,
            ];
        });
    }

    private function assertUserCanDeploy(User $user): void
    {
        if ($this->hasUnresolvedFailedDeploy($user)) {
            throw new \InvalidArgumentException(
                'Masih ada deploy yang gagal. Buka menu RDM Saya dan klik Deploy Ulang — tidak perlu bayar lagi.',
            );
        }

        $hasInstances = $user->rdmInstances()
            ->whereIn('status', ['active', 'expired', 'suspended'])
            ->exists();

        if ($hasInstances && ! $this->subscriptions->userHasActiveSubscription($user)) {
            throw new \InvalidArgumentException('Langganan habis. Perpanjang terlebih dahulu sebelum deploy RDM baru.');
        }
    }

    private function hasUnresolvedFailedDeploy(User $user): bool
    {
        return $user->rdmInstances()
            ->where('status', 'failed')
            ->whereHas('payments', function ($query) {
                $query->where('status', 'paid')->where('meta->purpose', 'deploy');
            })
            ->exists();
    }

    private function sendWelcomeIfNeeded(RdmInstance $instance, User $user, bool $accountCreated): void
    {
        if ($instance->deploy_welcome_sent_at) {
            return;
        }

        try {
            Mail::to($user->email)->send(new DeployPreparedMail($instance, $user, $accountCreated));
            $instance->update(['deploy_welcome_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim email deploy prepared '.$instance->fqdn.': '.$e->getMessage());
        }
    }

    private function schedulePaymentReminder(RdmInstance $instance): void
    {
        $minutes = (int) config('deploy.pending_payment_reminder_minutes', 5);

        SendDeployPaymentReminderJob::dispatch($instance->id)
            ->delay(now()->addMinutes($minutes));
    }
}
