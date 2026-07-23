@props([
    'height' => 'h-9',
])

<img
    src="{{ asset('logordm.png') }}"
    alt="RDM-ONLINE.ID"
    {{ $attributes->merge(['class' => "{$height} w-auto object-contain"]) }}
>
