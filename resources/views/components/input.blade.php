@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'required' => false,
    'currency' => false,
    'icon' => null,
    'placeholder' => null,
])

@php
    $error = $errors->first($name);
    $inputValue = old($name, $value);

    if ($currency && $inputValue !== null && $inputValue !== '') {
        $inputValue = number_format(
            is_numeric($inputValue) ? (float) $inputValue : (float) str_replace('.', '', (string) $inputValue),
            0,
            ',',
            '.',
        );
    }
@endphp

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-600 mb-1">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <div class="relative">
        @if($icon)
            <i class="fas {{ $icon }} absolute left-3 top-1/2 -translate-y-1/2 text-sm text-brand pointer-events-none"></i>
        @endif
        <input type="{{ $currency ? 'text' : $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ $inputValue }}"
               placeholder="{{ $placeholder }}" @required($required)
               @if($currency) inputmode="numeric" data-money-input pattern="[0-9.]*" @endif
               {{ $attributes->merge(['class' => 'w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 transition-all '.($icon ? 'pl-9' : '').($error ? ' border-red-400 focus:ring-red-200' : ' border-gray-200 focus:border-transparent focus:ring-brand-200')]) }}>
    </div>
    @if($error)
        <p class="text-xs text-red-600 mt-1"><i class="fas fa-circle-exclamation mr-1"></i>{{ $error }}</p>
    @endif
</div>
