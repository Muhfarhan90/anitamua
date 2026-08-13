@props([
    'name',
    'label' => null,
    'required' => false,
    'placeholder' => null,
    'rows' => 3,
    'value' => null,
])

@php $error = $errors->first($name); @endphp

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-600 mb-1">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <textarea name="{{ $name }}" id="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" @required($required)
              {{ $attributes->merge(['class' => 'w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 transition-all resize-none '.($error ? ' border-red-400 focus:ring-red-200' : ' border-gray-200 focus:border-transparent focus:ring-brand-200')]) }}>{{ old($name, $value ?? $slot) }}</textarea>
    @if($error)
        <p class="text-xs text-red-600 mt-1"><i class="fas fa-circle-exclamation mr-1"></i>{{ $error }}</p>
    @endif
</div>
