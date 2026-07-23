@props([
    'status' => 'Aktif',
    'variant' => 'success',
])

@php
    $classes = match ($variant) {
        'success' => 'bg-emerald-100 text-emerald-700',
        'warning' => 'bg-amber-100 text-amber-700',
        'danger' => 'bg-rose-100 text-rose-700',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

<span {{ $attributes->merge(['class' => "px-2 py-1 rounded-full text-xs font-medium {$classes}"]) }}>
    {{ $status }}
</span>
