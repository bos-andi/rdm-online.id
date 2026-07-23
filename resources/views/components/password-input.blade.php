@props([
    'id',
    'label',
    'placeholder' => '',
    'autocomplete' => 'new-password',
])

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1" for="{{ $id }}">{{ $label }}</label>
    <div class="relative">
        <input
            id="{{ $id }}"
            type="password"
            class="app-input pr-10"
            placeholder="{{ $placeholder }}"
            autocomplete="{{ $autocomplete }}"
            {{ $attributes }}
        >
        <button
            type="button"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-emerald-600 transition-colors"
            data-password-toggle
            data-target="{{ $id }}"
            aria-label="Tampilkan password"
        >
            <i data-lucide="eye" class="w-4 h-4 pointer-events-none"></i>
        </button>
    </div>
</div>
