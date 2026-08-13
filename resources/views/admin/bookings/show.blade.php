@extends('layouts.app')

@section('title', 'Detail Booking')

@section('content')
<x-page-header :title="'Detail Booking — '.($booking->client->name ?? $booking->name)">
    <x-slot:actions>
        <x-button href="{{ route('admin.bookings.packing', $booking) }}" color="primary"><i class="fas fa-box"></i> Packing Checklist</x-button>
        @if($booking->status !== 'completed' && $booking->status !== 'cancelled')
        <form action="{{ route('admin.bookings.complete', $booking) }}" method="POST" class="inline">
            @csrf
            <x-button color="success" type="submit" onclick="return confirm('Tandai booking ini sebagai selesai?')"><i class="fas fa-check"></i> Tandai Selesai</x-button>
        </form>
        <x-button color="danger" onclick="document.getElementById('cancelModal').classList.remove('hidden')"><i class="fas fa-ban"></i> Batalkan Booking</x-button>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- MAIN --}}
    <div class="lg:col-span-2 space-y-5">

        <x-card title="Informasi Booking" title-icon="fa-file-invoice">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Kode</span>
                    <p class="font-semibold text-gray-800"><x-badge>{{ $booking->code }}</x-badge></p>
                </div>
                <div>
                    <span class="text-gray-500">Tanggal Acara</span>
                    <p class="font-semibold text-gray-800">{{ $booking->event_date ? $booking->event_date->format('d M Y') : '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Lokasi</span>
                    <p class="font-semibold text-gray-800">{{ $booking->location ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Telepon</span>
                    <p class="font-semibold text-gray-800">{{ $booking->client->phone ?? '-' }}</p>
                </div>
            </div>
        </x-card>

        {{-- PROGRESS --}}
        <x-card title="Progress Booking" title-icon="fa-route">
            @php
                $steps = ['Booking', 'DP 10%', 'Hari H', 'Selesai'];
                $progressMap = ['pending' => 1, 'booked' => 2, 'completed' => 4, 'cancelled' => 0];
                $currentStep = $progressMap[$booking->status] ?? 1;
            @endphp
            <div class="flex items-center justify-between">
                @foreach($steps as $index => $step)
                    @php $stepNum = $index + 1; @endphp
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300
                                    {{ $stepNum <= $currentStep ? 'bg-brand text-white' : 'bg-gray-100 text-gray-400' }}">
                            {{ $stepNum }}
                        </div>
                        <span class="text-xs mt-2 text-center font-medium {{ $stepNum <= $currentStep ? 'text-brand' : 'text-gray-400' }}">{{ $step }}</span>
                    </div>
                    @if(!$loop->last)
                    <div class="flex-1 h-1 mx-1 rounded-full {{ $stepNum < $currentStep ? 'bg-brand' : 'bg-gray-200' }}"></div>
                    @endif
                @endforeach
            </div>
        </x-card>

        {{-- VERIFICATION --}}
        @if($booking->status === 'pending')
        <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-xl shadow-sm p-5">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-yellow-100 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-display text-lg font-bold text-yellow-800">Verifikasi DP 10%</h4>
                    <p class="text-sm text-yellow-700 mt-1">Booking ini belum diverifikasi. Silakan verifikasi pembayaran DP. Setelah diverifikasi, akun dashboard client dibuat otomatis.</p>
                    <form action="{{ route('admin.bookings.verify-dp', $booking) }}" method="POST" class="mt-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <x-input name="amount" label="Nominal" type="number" placeholder="500000" :value="$booking->package?->price * 0.1" />
                            <x-select name="method" label="Metode">
                                <option value="transfer">Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="qris">QRIS</option>
                            </x-select>
                            <div class="flex items-end">
                                <x-button color="success" type="submit" class="w-full"><i class="fas fa-check"></i> Verifikasi DP</x-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- PAYMENT TABLE --}}
        <x-card title="Pembayaran" title-icon="fa-credit-card" padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                            <th class="px-5 py-2.5 font-medium">Tahap</th>
                            <th class="px-5 py-2.5 font-medium">Nominal</th>
                            <th class="px-5 py-2.5 font-medium">Jatuh Tempo</th>
                            <th class="px-5 py-2.5 font-medium">Bukti</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($booking->payments as $payment)
                        <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                            <td class="px-5 py-3"><x-badge>{{ \App\Models\Payment::typeLabel($payment->type) }}</x-badge></td>
                            <td class="px-5 py-3 font-semibold text-gray-800">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $payment->due_date ? $payment->due_date->format('d M Y') : '-' }}</td>
                            <td class="px-5 py-3">
                                @if($payment->proof)
                                    <a href="{{ asset('storage/' . $payment->proof) }}" onclick="openProof(event, this.href)" class="text-brand underline text-sm cursor-pointer">Lihat</a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <x-badge :color="match($payment->status) {
                                    'pending' => 'warning',
                                    'verified' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }">{{ ucfirst($payment->status) }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($payment->status === 'pending')
                                <form action="{{ route('admin.payments.verify', $payment->id) }}" method="POST" class="inline">
                                    @csrf
                                    <x-button size="sm" color="success" type="submit">Verifikasi</x-button>
                                </form>
                                @else
                                <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6"><x-empty-state icon="fa-credit-card" title="Belum ada data pembayaran" /></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- SCHEDULE --}}
        <x-card title="Jadwal" title-icon="fa-calendar-days" padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                            <th class="px-5 py-2.5 font-medium">Jenis</th>
                            <th class="px-5 py-2.5 font-medium">Tanggal</th>
                            <th class="px-5 py-2.5 font-medium">Jam</th>
                            <th class="px-5 py-2.5 font-medium">Lokasi</th>
                            <th class="px-5 py-2.5 font-medium">PIC</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($booking->schedules as $schedule)
                        <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                            <td class="px-5 py-3">
                                <x-badge :color="match($schedule->type) {
                                    'survey' => 'info',
                                    'fitting' => 'brand',
                                    'hari_h' => 'danger',
                                    default => 'gray',
                                }">{{ ucfirst(str_replace('_', ' ', $schedule->type)) }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $schedule->date ? $schedule->date->format('d M Y') : '-' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $schedule->time ?? '-' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $schedule->location ?? '-' }}</td>
                            <td class="px-5 py-3">
                                <form action="{{ route('admin.schedules.pic', $schedule) }}" method="POST" class="inline">
                                    @csrf
                                    <select name="pic_user_id" onchange="this.form.submit()"
                                            class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-brand-200">
                                        <option value="" {{ ! $schedule->pic_user_id ? 'selected' : '' }}>— Belum ada PIC —</option>
                                        @foreach($teamMembers as $member)
                                            <option value="{{ $member->id }}" @selected($schedule->pic_user_id === $member->id)>{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-5 py-3">
                                <form action="{{ route('admin.schedules.status', $schedule) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" onchange="this.form.submit()"
                                            class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-brand-200">
                                        <option value="scheduled" {{ $schedule->status === 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
                                        <option value="on_going" {{ $schedule->status === 'on_going' ? 'selected' : '' }}>Berlangsung</option>
                                        <option value="finished" {{ $schedule->status === 'finished' ? 'selected' : '' }}>Selesai</option>
                                        <option value="cancelled" {{ $schedule->status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <form action="{{ route('admin.schedules.destroy', $schedule) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Hapus jadwal ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7"><x-empty-state icon="fa-calendar-xmark" title="Belum ada jadwal" /></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @php
                $existingTypes = $booking->schedules->pluck('type')->toArray();
                $allTypes = ['survey', 'fitting', 'hari_h'];
                $missingTypes = array_diff($allTypes, $existingTypes);
            @endphp

            @if(!empty($missingTypes))
            <div class="p-5 border-t border-dashed border-brand-200">
                <p class="text-sm font-semibold text-brand mb-3">Tambah Jadwal Baru</p>
                <form action="{{ route('admin.schedules.store') }}" method="POST" class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    @csrf
                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                    <select name="type" required class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
                        <option value="">Jenis</option>
                        @foreach($allTypes as $t)
                            <option value="{{ $t }}" {{ in_array($t, $existingTypes) ? 'disabled' : '' }}>
                                {{ \App\Models\Schedule::typeLabel($t) }}{{ in_array($t, $existingTypes) ? ' (sudah ada)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <input type="date" name="date" required class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
                    <input type="time" name="time" class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
                    <input type="text" name="location" placeholder="Lokasi" class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
                    <x-button type="submit"><i class="fas fa-plus"></i> Tambah</x-button>
                </form>
            </div>
            @else
            <div class="p-5 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-500"><i class="fas fa-check-circle text-emerald-500 mr-1"></i> Semua jadwal sudah terjadwal: Survey, Fitting, Hari H.</p>
            </div>
            @endif
        </x-card>

        {{-- SURVEY --}}
        <x-card title="Survey Lokasi" title-icon="fa-map-location-dot">
            @php $survey = $booking->survey; @endphp

            @if($survey)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                <div>
                    <span class="text-gray-500">Lokasi</span>
                    <p class="font-semibold text-gray-800">{{ $survey->location ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Maps</span>
                    @if($survey->maps_url)
                        <a href="{{ $survey->maps_url }}" target="_blank" class="text-brand no-underline font-medium">Buka Maps</a>
                    @else
                        <p class="font-semibold text-gray-800">-</p>
                    @endif
                </div>
                <div>
                    <span class="text-gray-500">PIC</span>
                    <p class="font-semibold text-gray-800">{{ $survey->pic ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Oleh</span>
                    <p class="font-semibold text-gray-800">{{ $survey->creator->name ?? '-' }}</p>
                </div>
            </div>
            @if($survey->notes)
            <div class="bg-cream/60 rounded-xl px-4 py-3 text-sm text-gray-600 mb-4">{{ $survey->notes }}</div>
            @endif
            @if($survey->photos)
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($survey->photos as $photo)
                    <a href="{{ asset('storage/'.$photo) }}" onclick="openProof(event, this.href)" class="cursor-pointer">
                        <img src="{{ asset('storage/'.$photo) }}" alt="Foto survey" class="w-20 h-20 object-cover rounded-xl border border-gray-100">
                    </a>
                @endforeach
            </div>
            @endif
            @endif

            <form action="{{ route('admin.fieldwork.survey') }}" method="POST" enctype="multipart/form-data" class="border-t border-dashed border-brand-200 pt-4">
                @csrf
                <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <x-input name="location" label="Lokasi" :value="$survey->location ?? ''" placeholder="Alamat tempat acara" />
                    <x-input name="maps_url" label="Link Maps" :value="$survey->maps_url ?? ''" placeholder="https://maps.app.goo.gl/..." />
                    <x-select name="pic" label="PIC (Tim Lapangan)">
                        <option value="">— Pilih Tim Lapangan —</option>
                        @foreach($teamMembers as $member)
                            <option value="{{ $member->name }}" @selected(($survey->pic ?? '') === $member->name)>{{ $member->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="mt-3">
                    <x-textarea name="notes" label="Catatan" rows="2">{{ $survey->notes ?? '' }}</x-textarea>
                </div>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <x-input name="photos[]" label="Foto" type="file" multiple accept="image/*" />
                    <x-input name="videos[]" label="Video" type="file" multiple accept="video/*" />
                </div>
                <div class="mt-4">
                    <x-button color="primary" type="submit"><i class="fas fa-save"></i> {{ $survey ? 'Perbarui Survey' : 'Simpan Survey' }}</x-button>
                </div>
            </form>
        </x-card>

        {{-- FITTING --}}
        <x-card title="Sesi Fitting" title-icon="fa-shirt">
            @php $fitting = $booking->fittings->first(); @endphp

            @if($fitting)
            <div class="flex items-center justify-between gap-4 border border-gray-100 rounded-xl px-4 py-3 mb-4 {{ $fitting->status === 'finished' ? 'bg-emerald-50/50' : 'bg-gray-50/60' }}">
                <div>
                    <p class="text-sm font-semibold text-gray-800">
                        {{ $fitting->date ? $fitting->date->format('d M Y') : '-' }}
                        @if($fitting->time)<span class="text-gray-400 font-normal"> · {{ $fitting->time->format('H:i') }}</span>@endif
                    </p>
                    <p class="text-xs text-gray-500">{{ $fitting->pic ? 'PIC: '.$fitting->pic : '' }}{{ $fitting->notes ? ' · '.$fitting->notes : '' }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($fitting->photos)
                        @foreach($fitting->photos as $photo)
                        <a href="{{ asset('storage/'.$photo) }}" onclick="openProof(event, this.href)" class="cursor-pointer">
                            <img src="{{ asset('storage/'.$photo) }}" alt="Foto fitting" class="w-10 h-10 object-cover rounded-lg border border-gray-100">
                        </a>
                        @endforeach
                    @endif
                    <x-badge :color="match($fitting->status) {
                        'finished' => 'success',
                        'on_going' => 'warning',
                        default => 'gray',
                    }">{{ ucfirst(str_replace('_', ' ', $fitting->status)) }}</x-badge>
                </div>
            </div>
            @else
            <x-empty-state icon="fa-shirt" title="Belum ada data fitting" />
            @endif

            {{-- Fitting hanya 1 per booking — form memperbarui record yang sama --}}
            <form action="{{ route('admin.fieldwork.fitting') }}" method="POST" enctype="multipart/form-data" class="border-t border-dashed border-brand-200 pt-4 mt-3">
                @csrf
                <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <x-input name="date" label="Tanggal" type="date" required :value="$fitting->date ? $fitting->date->format('Y-m-d') : ''" />
                    <x-input name="time" label="Jam" type="time" :value="$fitting->time ? $fitting->time->format('H:i') : ''" />
                    <x-select name="pic" label="PIC (Tim Lapangan)">
                        <option value="">— Pilih Tim Lapangan —</option>
                        @foreach($teamMembers as $member)
                            <option value="{{ $member->name }}" @selected(($fitting->pic ?? '') === $member->name)>{{ $member->name }}</option>
                        @endforeach
                    </x-select>
                    <div>
                        <label for="fitting_status" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                        <select name="status" id="fitting_status" required
                                class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                            <option value="scheduled" @selected($fitting && $fitting->status === 'scheduled')>Scheduled</option>
                            <option value="on_going" @selected($fitting && $fitting->status === 'on_going')>On Going</option>
                            <option value="finished" @selected($fitting && $fitting->status === 'finished')>Finished</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <x-textarea name="notes" label="Catatan" rows="2">{{ $fitting->notes ?? '' }}</x-textarea>
                </div>
                <div class="mt-3">
                    <x-input name="photos[]" label="Foto" type="file" multiple accept="image/*" />
                </div>
                <div class="mt-4">
                    <x-button color="primary" type="submit"><i class="fas fa-save"></i> {{ $fitting ? 'Perbarui Fitting' : 'Simpan Fitting' }}</x-button>
                </div>
            </form>
        </x-card>

        {{-- ACTIVITY --}}
        <x-card title="Aktivitas" title-icon="fa-clock-rotate-left">
            <div class="relative pl-6 border-l-2 border-brand-100 space-y-5">
                @forelse($booking->activityLogs ?? [] as $activity)
                <div class="relative">
                    <div class="absolute -left-[31px] top-1 w-3 h-3 rounded-full border-2 border-white shadow bg-brand"></div>
                    <p class="text-xs text-gray-400">{{ $activity->created_at ? $activity->created_at->format('d M Y H:i') : '' }} Â· <span class="font-semibold text-brand">{{ $activity->user->name ?? 'Sistem' }}</span></p>
                    <p class="text-sm text-gray-700">{{ $activity->description }}</p>
                </div>
                @empty
                <x-empty-state icon="fa-clock-rotate-left" title="Belum ada aktivitas" />
                @endforelse
            </div>
        </x-card>
    </div>

    {{-- SIDEBAR --}}
    <div class="space-y-5">
        <x-card title="Paket" title-icon="fa-gift">
            @if(isset($booking->package))
            <div class="p-4 rounded-xl bg-brand-50">
                <h4 class="font-display font-bold text-brand">{{ $booking->package->name }}</h4>
                <p class="font-display text-lg font-bold text-gray-800 mt-1">Rp {{ number_format($booking->package->price, 0, ',', '.') }}</p>
                @if(isset($booking->package->benefits))
                <ul class="mt-3 space-y-1">
                    @foreach($booking->package->benefits as $benefit)
                    <li class="flex items-center gap-2 text-xs text-gray-700">
                        <i class="fas fa-circle-check text-emerald-500 text-xs"></i>{{ $benefit->name }}
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
            @endif
        </x-card>
    </div>
</div>

{{-- CANCEL MODAL --}}
<div id="cancelModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="document.getElementById('cancelModal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full mx-auto p-8">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-red-100">
                <i class="fas fa-ban text-2xl text-red-600"></i>
            </div>
            <h3 class="font-display text-xl font-bold text-center mb-2 text-gray-900">Batalkan Booking?</h3>
            <p class="text-sm text-center text-gray-500 mb-6">Booking <strong>{{ $booking->code }}</strong> akan dibatalkan. DP dinyatakan hangus. Tindakan ini tidak dapat dibatalkan.</p>
            <form action="{{ route('admin.bookings.cancel', $booking) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Pembatalan</label>
                    <textarea name="reason" rows="3" required placeholder="Masukkan alasan pembatalan..."
                              class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')"
                            class="flex-1 px-6 py-2.5 rounded-xl text-sm font-semibold border-2 border-gray-300 text-gray-600 hover:bg-gray-50 transition-all">
                        Batal
                    </button>
                    <x-button color="danger" type="submit" class="flex-1 justify-center">Ya, Batalkan</x-button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
