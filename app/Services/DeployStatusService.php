<?php

namespace App\Services;

use App\Models\RdmInstance;

class DeployStatusService
{
    public function payload(RdmInstance $instance): array
    {
        $steps = collect(config('deploy.steps'))->map(function ($step) use ($instance) {
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
            'url' => 'https://'.$instance->fqdn,
            'error' => $instance->error_message,
            'steps' => $steps,
            'logs' => $instance->logs()->latest()->limit(20)->get(['step', 'message', 'progress', 'level', 'created_at']),
        ];
    }
}
