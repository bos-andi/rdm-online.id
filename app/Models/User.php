<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'madrasah_name', 'jenjang', 'email', 'password', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function rdmInstances(): HasMany
    {
        return $this->hasMany(RdmInstance::class);
    }

    public function jenjangLabel(): string
    {
        return match ($this->jenjang) {
            'RA' => 'RA (Raudhatul Athfal)',
            'MI' => 'MI (Madrasah Ibtidaiyah)',
            'MTs' => 'MTs (Madrasah Tsanawiyah)',
            'MA' => 'MA (Madrasah Aliyah)',
            default => $this->jenjang ?? '—',
        };
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}
