@props([
    'label',
    'value',
    'valueClass' => 'text-emerald-600',
])

<div {{ $attributes->merge(['class' => 'admin-stat-card']) }}>
    <p class="text-slate-500 text-sm">{{ $label }}</p>
    <p class="text-3xl font-bold mt-1 {{ $valueClass }}">{{ $value }}</p>
</div>
