@extends('layouts.app')

@section('title', $package ? 'Edit Paket' : 'Buat Paket')

@section('content')
<x-page-header :title="$package ? 'Edit Paket — '.$package->name : 'Buat Paket Baru'" />

<x-card padding="p-6" class="max-w-4xl">
    <form method="POST" action="{{ $package ? route('admin.packages.update', $package) : route('admin.packages.store') }}" class="space-y-5">
        @csrf
        @if($package) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <x-input name="name" label="Nama Paket" required :value="$package->name ?? ''" />
            <div>
                <label for="type" class="block text-sm font-medium text-gray-600 mb-1">Jenis Paket <span class="text-red-500">*</span></label>
                <select name="type" id="type" required
                        class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="makeup" @selected(old('type', $package->type ?? '') === 'makeup')>Make Up & Attire</option>
                    <option value="full" @selected(old('type', $package->type ?? '') === 'full')>Full WO Package</option>
                </select>
            </div>
            <x-input name="price" label="Harga" type="number" :value="$package->price ?? 0" min="0" step="1000" required />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="md:col-span-2">
                <x-textarea name="description" label="Deskripsi" rows="2" :value="$package->description ?? ''" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Warna Tema</label>
                <div class="flex gap-2 items-center">
                    <input type="color" name="color" value="{{ old('color', $package->color ?? '#c9a227') }}"
                           class="h-[38px] w-14 rounded-lg border border-gray-200 bg-gray-50 cursor-pointer">
                    <x-select name="status">
                        <option value="active" @selected(old('status', $package->status ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $package->status ?? '') === 'inactive')>Inactive</option>
                    </x-select>
                </div>
            </div>
        </div>

        <div x-data="{ showBenefits: false }">
            <label class="block text-sm font-medium text-gray-600 mb-2">Kategori Benefit yang Dipakai <span class="text-red-500">*</span></label>
            @php
                $selectedCategories = old('benefit_category_ids', $package?->benefitCategories->pluck('id')->toArray() ?? []);
                $selectedBenefits = old('benefit_ids', $package?->benefits->pluck('id')->toArray() ?? []);
                $packageBenefits = $package?->benefits ?? collect();
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2 mb-4">
                @foreach($benefitCategories as $category)
                    @php
                        $selectedInCategory = $packageBenefits->where('benefit_category_id', $category->id)->count();
                    @endphp
                    <label class="flex items-center gap-2 border border-gray-200 rounded-lg px-3 py-2 cursor-pointer hover:border-brand-200 transition-colors has-[:checked]:border-brand has-[:checked]:bg-brand-50/40"
                           onclick="toggleBenefitGroup({{ $category->id }}, this)">
                        <input class="accent-[#d4739a] category-check" type="checkbox" name="benefit_category_ids[]" value="{{ $category->id }}" data-category="{{ $category->id }}"
                               @checked(in_array($category->id, $selectedCategories))>
                        <span class="text-sm w-full">
                            <strong class="text-gray-800">{{ $category->name }}</strong>
                            <span class="block text-xs text-gray-500">{{ $selectedInCategory }} benefit dipilih</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <label class="block text-sm font-medium text-gray-600 mb-2">Benefit (dari Master Benefit)</label>
            @php
                $groupedBenefits = $benefits->groupBy(fn ($b) => $b->category->id);
            @endphp
            @forelse($benefitCategories as $category)
                @php $items = $groupedBenefits->get($category->id, collect()); @endphp
                <div class="benefit-group mb-4" data-category="{{ $category->id }}" style="display: {{ in_array($category->id, $selectedCategories) ? 'block' : 'none' }}">
                    <p class="text-xs font-bold text-brand uppercase tracking-wider mb-2">{{ $category->name }}</p>
                    @if($items->isNotEmpty())
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($items as $benefit)
                                <div class="flex items-center gap-2 border border-gray-200 rounded-lg px-3 py-2 hover:border-brand-200 transition-colors">
                                    <input class="accent-[#d4739a]" type="checkbox" name="benefit_ids[]" value="{{ $benefit->id }}" id="benefit{{ $benefit->id }}"
                                           @checked(in_array($benefit->id, $selectedBenefits))>
                                    <label class="text-sm w-full cursor-pointer" for="benefit{{ $benefit->id }}">{{ $benefit->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400">Belum ada benefit di kategori ini.</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-400">Belum ada kategori benefit. <a href="{{ route('admin.benefit-categories.index') }}" class="text-brand no-underline font-medium">Tambah kategori dulu</a>.</p>
            @endforelse
            <p class="text-xs text-gray-400 mt-2">Pilih kategori dulu, lalu centang benefit dari kategori tersebut. Benefit diambil dari Master Benefit agar bisa dipakai ulang di banyak paket.</p>
        </div>

        <div class="flex gap-2 pt-4 border-t border-brand-100">
            <x-button color="primary" type="submit"><i class="fas fa-save"></i> Simpan Paket</x-button>
            <x-button color="ghost" href="{{ route('admin.packages.index') }}">Batal</x-button>
        </div>
    </form>
</x-card>

@push('scripts')
<script>
    function toggleBenefitGroup(categoryId, labelEl) {
        const checkbox = labelEl.querySelector('input[type="checkbox"]');
        const group = document.querySelector('.benefit-group[data-category="' + categoryId + '"]');
        if (group) {
            group.style.display = checkbox.checked ? 'block' : 'none';
            if (!checkbox.checked) {
                group.querySelectorAll('input[name="benefit_ids[]"]').forEach(input => input.checked = false);
            }
        }
    }
</script>
@endpush
@endsection
