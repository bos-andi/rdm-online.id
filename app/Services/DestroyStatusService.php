<?php

namespace App\Services;

use App\Models\RdmInstance;

class DestroyStatusService
{
    public function payload(RdmInstance $instance): array
    {
        $steps = collect(config('deploy.destroy_steps'))->map(function ($step) use ($instance) {
            $completed = $instance->logs()
                ->where('step', $step['key'])
                ->where(function ($query) {
                    $query->where('message', 'like', '%selesai%')
                        ->orWhere('message', 'like', '%dilewati%');
                })
                ->exists();

            $running = $instance->current_step === $step['key'] && ! $completed;

            return [
                'key' => $step['key'],
                'label' => $step['label'],
                'progress' => $step['progress'],
                'status' => $completed ? 'completed' : ($running ? 'running' : 'pending'),
            ];
        });

        return [
            'id' => $instance->id,
            'status' => $instance->status,
            'progress' => $instance->progress,
            'current_step' => $instance->current_step,
            'fqdn' => $instance->fqdn,
            'error' => $instance->error_message,
            'destroyed' => false,
            'steps' => $steps,
            'logs' => $instance->logs()->latest()->limit(20)->get(['step', 'message', 'progress', 'level', 'created_at']),
        ];
    }

    public function destroyedPayload(string $fqdn): array
    {
        return [
            'status' => 'destroyed',
            'progress' => 100,
            'fqdn' => $fqdn,
            'destroyed' => true,
            'steps' => collect(config('deploy.destroy_steps'))->map(fn ($step) => [
                'key' => $step['key'],
                'label' => $step['label'],
                'progress' => $step['progress'],
                'status' => 'completed',
            ])->values(),
        ];
    }
}
