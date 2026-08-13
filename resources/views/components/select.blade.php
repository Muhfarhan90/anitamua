@props([
    'name',
    'label' => null,
    'required' => false,
    'placeholder' => null,
])

@php $error = $errors->first($name); @endphp

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-600 mb-1">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <select name="{{ $name }}" id="{{ $name }}" @required($required)
            {{ $attributes->merge(['class' => 'w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 transition-all '.($error ? ' border-red-400 focus:ring-red-200' : ' border-gray-200 focus:border-transparent focus:ring-brand-200')]) }}>
        @if($placeholder)<option value="">{{ $placeholder }}</option>@endif
        {{ $slot }}
    </select>
    @if($error)
        <p class="text-xs text-red-600 mt-1"><i class="fas fa-circle-exclamation mr-1"></i>{{ $error }}</p>
    @endif
</div>
