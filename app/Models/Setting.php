<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::rememberForever('setting.'.$key, function () use ($key, $default) {
                return static::query()->where('key', $key)->value('value') ?? $default;
            });
        } catch (\Throwable) {
            return static::query()->where('key', $key)->value('value') ?? $default;
        }
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value],
        );

        try {
            Cache::forget('setting.'.$key);
        } catch (\Throwable) {
            // ignore cache write failures
        }
    }
}
