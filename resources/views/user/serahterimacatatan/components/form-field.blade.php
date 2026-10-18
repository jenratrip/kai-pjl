
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
])

<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-sm font-semibold text-slate-700">
        {{ $label }}
    </label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-blue-100'
        ]) }}
    >

    @error($name)
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
