@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm']) }}>
