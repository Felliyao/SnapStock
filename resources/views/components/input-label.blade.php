@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-slate-800']) }}>
    {{ $value ?? $slot }}
</label>
