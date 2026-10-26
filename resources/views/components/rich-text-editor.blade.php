@props(['name', 'label', 'value' => '', 'help' => null])
@php $fieldId = 'rich-text-'.\Illuminate\Support\Str::slug($name); @endphp

<div data-rich-text-editor data-label="{{ $label }}" class="rich-text-editor">
    <label for="{{ $fieldId }}" class="mb-2 block text-sm font-medium text-gray-600">{{ $label }}</label>
    @if($help)<p class="mb-3 text-xs text-gray-500">{{ $help }}</p>@endif
    <textarea id="{{ $fieldId }}" name="{{ $name }}" data-rich-text-input class="min-h-48 w-full rounded-lg border border-gray-200 bg-white p-3 text-sm">{{ $value }}</textarea>
    <div data-rich-text-canvas class="hidden min-h-48 bg-white"></div>
    @error($name)<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
