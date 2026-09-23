@extends('layouts.app')

@section('title', 'Testimoni Saya')

@section('content')
<x-page-header title="Testimoni Saya" subtitle="Bagikan pengalaman dari booking yang telah selesai." />

<div class="mx-auto max-w-5xl space-y-4 pb-10">
    @forelse($bookings as $booking)
        @php
            $testimonial = $booking->testimonial;
            $photoPaths = array_values(array_filter($testimonial?->photos ?: [$testimonial?->photo]));
            $photoUrls = $testimonial?->photo_urls ?? [];
            $useOldInput = (int) old('booking_id') === $booking->id;
            $selectedRating = (int) ($useOldInput ? old('rating') : ($testimonial?->rating ?? 5));
        @endphp

        <x-card padding="p-4" x-data="{ editing: {{ $testimonial && ! $useOldInput ? 'false' : 'true' }} }">
            @if($testimonial)
                <div x-show="!editing">
                    @if($photoUrls)
                        <div class="mb-4">
                            <x-gallery-slider :photos="$photoUrls" :title="'Foto '.$testimonial->client_name" height="220px" />
                        </div>
                    @endif

                    <div class="mb-3 text-brand text-lg">
                        @for($i = 0; $i < $testimonial->rating; $i++)<i class="fas fa-star"></i>@endfor
                    </div>
                    <p class="mb-5 text-base italic leading-7 text-gray-600">“{{ $testimonial->content }}”</p>

                    <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand font-bold text-white">
                                {{ strtoupper(substr($testimonial->client_name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">{{ $testimonial->client_name }}</p>
                                <p class="mt-0.5 text-xs uppercase tracking-wider text-gray-400">Booking {{ $booking->code }} · {{ $booking->event_date?->format('d M Y') ?? '-' }}</p>
                            </div>
                        </div>
                        <x-button type="button" color="outline" size="sm" @click="editing = true">
                            <i class="fas fa-pen"></i> Edit Testimoni
                        </x-button>
                    </div>
                </div>
            @else
                <div class="mb-5 flex items-center gap-3 border-b border-gray-100 pb-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">{{ $booking->name }}</p>
                        <p class="mt-0.5 text-xs uppercase tracking-wider text-gray-400">Booking {{ $booking->code }} · {{ $booking->event_date?->format('d M Y') ?? '-' }}</p>
                    </div>
                </div>
            @endif

            <div x-show="editing" @if($testimonial) x-cloak class="mt-5 border-t border-gray-100 pt-5" @endif>
                <form action="{{ route('client.booking.testimonial', $booking) }}" method="POST" enctype="multipart/form-data" class="space-y-5" data-testimonial-form>
                    @csrf
                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">

                    <div>
                        <p class="mb-2 text-sm font-medium text-gray-700">Rating <span class="text-red-500">*</span></p>
                        <div class="testimonial-stars" aria-label="Pilih rating testimoni">
                            @for($rating = 5; $rating >= 1; $rating--)
                                <input id="testimonial-rating-{{ $booking->id }}-{{ $rating }}" type="radio" name="rating" value="{{ $rating }}" @checked($selectedRating === $rating) required>
                                <label for="testimonial-rating-{{ $booking->id }}-{{ $rating }}" title="{{ $rating }} bintang" aria-label="{{ $rating }} bintang"><i class="fas fa-star"></i></label>
                            @endfor
                        </div>
                        @if($useOldInput) @error('rating')<p class="mt-1 text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror @endif
                    </div>

                    <div>
                        <label for="testimonial-content-{{ $booking->id }}" class="mb-1 block text-sm font-medium text-gray-600">Testimoni <span class="text-red-500">*</span></label>
                        <textarea name="content" id="testimonial-content-{{ $booking->id }}" rows="4" required placeholder="Ceritakan pengalaman Anda bersama tim ANITA..." class="w-full resize-none rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 transition-all focus:outline-none focus:ring-2 {{ $useOldInput && $errors->has('content') ? 'border-red-400 focus:ring-red-200' : 'border-gray-200 focus:border-transparent focus:ring-brand-200' }}">{{ $useOldInput ? old('content') : $testimonial?->content }}</textarea>
                        @if($useOldInput) @error('content')<p class="mt-1 text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror @endif
                    </div>

                    @if($photoUrls)
                        <div>
                            <p class="mb-2 text-sm font-medium text-gray-700">Foto saat ini</p>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                                @foreach($photoUrls as $index => $photoUrl)
                                    <div class="overflow-hidden rounded-xl border border-gray-100 bg-gray-50">
                                        <button type="button" data-gallery-lightbox data-gallery-photos="{{ base64_encode(json_encode($photoUrls)) }}" data-gallery-title="Foto {{ $testimonial->client_name }}" aria-label="Perbesar foto testimoni {{ $loop->iteration }}" class="block w-full cursor-zoom-in">
                                            <img src="{{ $photoUrl }}" alt="Foto testimoni {{ $loop->iteration }}" class="h-28 w-full object-cover">
                                        </button>
                                        <label class="flex cursor-pointer items-center justify-center gap-1.5 border-t border-red-100 bg-red-50 px-2 py-2 text-xs font-medium text-red-700 hover:bg-red-100">
                                            <input type="checkbox" name="remove_photos[]" value="{{ $photoPaths[$index] ?? '' }}" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                            <span><i class="fas fa-trash-can mr-1"></i>Hapus</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <label for="testimonialPhotos-{{ $booking->id }}" class="mb-1 block text-sm font-medium text-gray-700">{{ $testimonial ? 'Tambah Foto' : 'Foto' }} <span class="font-normal text-gray-400">(opsional)</span></label>
                        <input id="testimonialPhotos-{{ $booking->id }}" data-testimonial-photos name="photos[]" type="file" accept="image/*" multiple class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-semibold file:text-brand hover:file:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        <p class="mt-1 text-xs text-gray-400">Bisa memilih beberapa foto, maksimal 3 MB per foto.</p>
                        @if($useOldInput)
                            @error('photos')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            @error('photos.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        @endif
                        <div data-testimonial-preview class="mt-3 hidden grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"></div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-button color="primary" type="submit">
                            <i class="fas {{ $testimonial ? 'fa-save' : 'fa-paper-plane' }}"></i>
                            {{ $testimonial ? 'Simpan Perubahan' : 'Kirim Testimoni' }}
                        </x-button>
                        @if($testimonial)
                            <x-button color="ghost" type="button" @click="editing = false">Batal</x-button>
                        @endif
                    </div>
                </form>
            </div>
        </x-card>
    @empty
        <x-card>
            <x-empty-state icon="fa-star" title="Belum ada booking yang selesai" text="Form testimoni akan tersedia setelah status booking Anda selesai." />
        </x-card>
    @endforelse
</div>

<x-gallery-lightbox />
@endsection

@push('styles')
<style>
    .testimonial-stars { display:flex; flex-direction:row-reverse; justify-content:flex-end; gap:.35rem; }
    .testimonial-stars input { position:absolute; opacity:0; pointer-events:none; }
    .testimonial-stars label { cursor:pointer; color:#e5e7eb; font-size:1.75rem; line-height:1; transition:color .15s ease, transform .15s ease; }
    .testimonial-stars input:checked ~ label,
    .testimonial-stars label:hover,
    .testimonial-stars label:hover ~ label { color:#f59e0b; }
    .testimonial-stars label:hover { transform:translateY(-1px); }
    .testimonial-stars input:focus-visible + label { border-radius:.35rem; outline:2px solid #d4739a; outline-offset:3px; }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('[data-testimonial-photos]').forEach(function (input) {
    input.addEventListener('change', function () {
        const preview = this.closest('div').querySelector('[data-testimonial-preview]');
        const items = Array.from(this.files).map(function (file) {
            const wrapper = document.createElement('div');
            wrapper.className = 'overflow-hidden rounded-xl border border-brand-100 bg-brand-50/40';

            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = file.name;
            image.className = 'h-28 w-full object-cover';
            image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });

            const name = document.createElement('p');
            name.className = 'truncate px-2 py-1.5 text-xs text-gray-500';
            name.textContent = file.name;
            wrapper.append(image, name);

            return wrapper;
        });

        preview.replaceChildren(...items);
        preview.classList.toggle('hidden', items.length === 0);
        preview.classList.toggle('grid', items.length > 0);
    });
});
</script>
@endpush
