@props([
    'label',
    'value',
    'valueClass' => 'text-emerald-600',
])

<div {{ $attributes->merge(['class' => 'stat-card']) }}>
    <p class="text-muted text-sm">{{ $label }}</p>
    <p class="text-3xl font-bold mt-1 {{ $valueClass }}">{{ $value }}</p>
</div>
