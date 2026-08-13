@extends('layouts.app')

@section('title', 'FAQ')

@section('content')
<x-page-header title="Konten Website — FAQ" subtitle="Pertanyaan yang tampil di halaman FAQ landing — seret (drag) untuk mengatur urutan">
    <x-slot:actions>
        <x-button href="{{ route('admin.content.testimonials') }}" color="ghost"><i class="fas fa-quote-right"></i> Testimoni</x-button>
        <x-button href="{{ route('admin.content.gallery') }}" color="ghost"><i class="fas fa-image"></i> Galeri</x-button>
        <x-button href="{{ route('admin.content.settings') }}" color="ghost"><i class="fas fa-gear"></i> Pengaturan</x-button>
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- FORM TAMBAH --}}
    <div>
        <x-card title="Tambah FAQ" title-icon="fa-circle-question">
            <form action="{{ route('admin.content.faqs.store') }}" method="POST" class="space-y-3">
                @csrf
                <x-input name="question" label="Pertanyaan" required placeholder="cth: Berapa lama proses booking?" />
                <x-textarea name="answer" label="Jawaban" rows="4" required></x-textarea>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                    <select name="status" id="status"
                            class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                        <option value="published">Published</option>
                        <option value="hidden">Hidden</option>
                    </select>
                </div>
                <p class="text-xs text-gray-400">FAQ baru otomatis masuk urutan terakhir. Atur urutan dengan drag pada daftar di samping.</p>
                <x-button color="primary" type="submit" class="w-full mt-1"><i class="fas fa-plus"></i> Tambah</x-button>
            </form>
        </x-card>
    </div>

    {{-- DAFTAR (Sortable) --}}
    <div class="lg:col-span-2">
        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                            <th class="px-5 py-2.5 font-medium w-10"></th>
                            <th class="px-5 py-2.5 font-medium">Pertanyaan</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="faqSortable">
                        @forelse($faqs as $faq)
                        <tr data-id="{{ $faq->id }}" class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                            <td class="px-5 py-3">
                                <span class="drag-handle inline-flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-brand hover:bg-brand-50 cursor-grab active:cursor-grabbing" title="Seret untuk mengatur urutan">
                                    <i class="fas fa-grip-vertical text-sm"></i>
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-gray-800">{{ $faq->question }}</p>
                                <p class="text-xs text-gray-500 max-w-[380px] truncate">{{ $faq->answer }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <x-badge :color="$faq->status === 'published' ? 'success' : 'gray'">{{ $faq->status }}</x-badge>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <x-button size="sm" color="outline" onclick="document.getElementById('edit-{{ $faq->id }}').classList.remove('hidden')"><i class="fas fa-pen"></i></x-button>
                                    <form method="POST" action="{{ route('admin.content.faqs.destroy', $faq) }}" onsubmit="return confirm('Hapus FAQ ini?')">
                                        @csrf @method('DELETE')
                                        <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4"><x-empty-state icon="fa-circle-question" title="Belum ada FAQ" /></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>

{{-- MODAL EDIT --}}
@foreach($faqs as $faq)
<div id="edit-{{ $faq->id }}" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('edit-{{ $faq->id }}').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display font-bold text-gray-800">Edit FAQ</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('edit-{{ $faq->id }}').classList.add('hidden')">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form action="{{ route('admin.content.faqs.update', $faq) }}" method="POST" class="space-y-3">
            @csrf @method('PUT')
            <x-input name="question" label="Pertanyaan" :value="$faq->question" required />
            <x-textarea name="answer" label="Jawaban" rows="4" required>{{ $faq->answer }}</x-textarea>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                <select name="status" id="status"
                        class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="published" @selected($faq->status === 'published')>Published</option>
                    <option value="hidden" @selected($faq->status === 'hidden')>Hidden</option>
                </select>
            </div>
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan</x-button>
        </form>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
    const faqTable = document.getElementById('faqSortable');

    if (faqTable) {
        new Sortable(faqTable, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'bg-brand-50',
            onEnd() {
                const order = Array.from(faqTable.querySelectorAll('tr[data-id]')).map(row => row.dataset.id);

                fetch('{{ route('admin.content.faqs.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ order }),
                });
            },
        });
    }
</script>
@endpush
