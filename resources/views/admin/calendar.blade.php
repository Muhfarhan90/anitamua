@extends('layouts.app')

@section('title', 'Kalender Jadwal')

@push('styles')
<style>
    .cal-cell {
        min-height: 110px;
        border-right: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
        padding: 8px;
        cursor: pointer;
        transition: background .15s ease;
    }
    .cal-cell:hover { background: #fdf2f8; }
    .cal-cell.is-today { background: #fce7f3; }
    .cal-cell.is-selected { background: #fdf2f8; outline: 2px solid #d4739a; outline-offset: -2px; border-radius: 2px; }
    .cal-cell.out-month { background: #fafafa; }
    .cal-cell.out-month .cal-day-num { color: #d1d5db; }
    .day-pill {
        width: 28px; height: 28px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 50%; font-size: .875rem; font-weight: 500;
    }
    .day-pill.today-pill {
        background: #d4739a; color: #fff; font-weight: 700;
    }
    #dayDetail {
        transition: opacity .25s ease, transform .25s ease;
    }
    #dayDetail.hidden { display: none !important; }
    #dayDetail.animate-in {
        animation: slideDown .25s ease forwards;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<x-page-header title="Kalender Jadwal" subtitle="Semua jadwal survey, fitting, dan Hari H">
    @if(in_array(auth()->user()->role, ['owner', 'admin']))
    <x-slot:actions>
        <x-button onclick="openModal()" class="!bg-brand !text-white hover:!bg-brand-dark" style="background:#d4739a;color:#fff;"><i class="fas fa-plus"></i> Tambah Jadwal</x-button>
    </x-slot:actions>
    @endif
</x-page-header>

{{-- LEGEND --}}
<div class="flex items-center gap-4 mb-4 flex-wrap">
    <span class="text-xs text-gray-500 font-medium">Keterangan:</span>
    <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded-sm inline-block" style="background:#dbeafe;"></span><span class="text-gray-600">Survey</span></span>
    <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded-sm inline-block" style="background:#fce7f3;"></span><span class="text-gray-600">Fitting</span></span>
    <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded-sm inline-block" style="background:#fee2e2;"></span><span class="text-gray-600">Hari H</span></span>
</div>

{{-- CALENDAR CARD --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">

    {{-- MONTH NAV --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <button onclick="navigateMonth(-1)"
                class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-brand-50 transition-colors text-gray-500 hover:text-brand">
            <i class="fas fa-chevron-left text-sm"></i>
        </button>
        <h2 class="font-display text-xl font-bold text-gray-800" id="currentMonth">{{ $date->translatedFormat('F Y') }}</h2>
        <button onclick="navigateMonth(1)"
                class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-brand-50 transition-colors text-gray-500 hover:text-brand">
            <i class="fas fa-chevron-right text-sm"></i>
        </button>
    </div>

    {{-- DAY HEADERS --}}
    <div class="grid grid-cols-7 border-b border-gray-100">
        @foreach(['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $i => $day)
        <div class="py-3 text-center text-[11px] font-semibold uppercase tracking-widest {{ $i === 0 ? 'text-rose-400' : 'text-gray-400' }}">{{ $day }}</div>
        @endforeach
    </div>

    {{-- CALENDAR GRID --}}
    <div class="grid grid-cols-7" id="calendarGrid">
        @foreach($calendar as $day)
        <div class="cal-cell {{ $day['inMonth'] ? '' : 'out-month' }} {{ $day['isToday'] ? 'is-today' : '' }}"
             id="cell-{{ $day['date']->format('Y-m-d') }}"
             onclick="selectDay('{{ $day['date']->format('Y-m-d') }}')">
            <div class="mb-1.5">
                <span class="cal-day-num day-pill {{ $day['isToday'] ? 'today-pill' : '' }}">
                    {{ $day['date']->format('d') }}
                </span>
            </div>
            <div class="space-y-0.5">
                @foreach($day['schedules'] as $event)
                @php
                    $typeColors = [
                        'survey'  => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'fitting' => ['bg' => '#fce7f3', 'text' => '#be185d'],
                        'hari_h'  => ['bg' => '#fee2e2', 'text' => '#b91c1c'],
                    ];
                    $tc = $typeColors[$event->type] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                @endphp
                <div class="text-[11px] px-1.5 py-0.5 rounded-md truncate font-medium leading-5"
                     style="background-color: {{ $tc['bg'] }}; color: {{ $tc['text'] }};">
                    {{ $event->title ?: \App\Models\Schedule::typeLabel($event->type).' — '.$event->booking->name }}
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- MODAL INFO JADWAL (popup saat klik sel kalender) --}}
<div id="dayInfoModal" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-[1100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col max-h-[85vh] overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-brand-50 flex items-center justify-center">
                    <i class="fas fa-calendar-day text-brand text-sm"></i>
                </div>
                <h2 class="font-display font-semibold text-base text-gray-800" id="infoDayTitle">—</h2>
            </div>
            <button onclick="closeInfo()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="p-5 overflow-y-auto" id="infoDayEvents">
            {{-- filled by JS --}}
        </div>
    </div>
</div>

{{-- MODAL TAMBAH JADWAL --}}
<div id="addModal" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col max-h-[85vh] overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <h3 class="font-display text-lg font-bold text-gray-800">Tambah Jadwal</h3>
            <button onclick="closeModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form action="{{ route('admin.schedules.store') }}" method="POST" class="p-5 space-y-3 overflow-y-auto">
            @csrf
            <x-select name="booking_id" label="Booking" required placeholder="Pilih booking">
                @foreach($bookings as $booking)
                <option value="{{ $booking->id }}">{{ $booking->code }} – {{ $booking->name }}</option>
                @endforeach
            </x-select>
            <x-select name="type" label="Jenis" required>
                <option value="survey">Survey</option>
                <option value="fitting">Fitting</option>
                <option value="hari_h">Hari H</option>
            </x-select>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-input name="date"     label="Tanggal" type="date" required />
                <x-input name="time"     label="Jam"     type="time" />
            </div>
            <x-select name="pic_user_id" label="PIC (Tim Lapangan)">
                <option value="">— Pilih Tim Lapangan —</option>
                @foreach($teamMembers as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            </x-select>
            <x-input    name="location" label="Lokasi"  placeholder="Nama lokasi / alamat" />
            <x-textarea name="notes"    label="Catatan" placeholder="Catatan opsional..." rows="2" />
            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                <x-button color="ghost" type="button" onclick="closeModal()">Batal</x-button>
                <x-button type="submit" class="!bg-brand !text-white hover:!bg-brand-dark" style="background:#d4739a;color:#fff;"><i class="fas fa-save"></i> Simpan Jadwal</x-button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const eventsByDate = @json($eventsByDate);
    let selectedCell   = null;

    /* ── Month navigation ── */
    function navigateMonth(dir) {
        const params = new URLSearchParams(window.location.search);
        let month = parseInt(params.get('month') || {{ $date->month }});
        let year  = parseInt(params.get('year')  || {{ $date->year }});
        month += dir;
        if (month > 12) { month = 1; year++; }
        if (month < 1)  { month = 12; year--; }
        window.location.search = `month=${month}&year=${year}`;
    }

    /* ── Select a day cell → tampilkan popup info + CTA detail booking ── */
    function selectDay(dateStr) {
        /* Highlight selected cell */
        if (selectedCell) selectedCell.classList.remove('is-selected');
        const cell = document.getElementById('cell-' + dateStr);
        if (cell) { cell.classList.add('is-selected'); selectedCell = cell; }

        /* Popup elements */
        const modal  = document.getElementById('dayInfoModal');
        const title  = document.getElementById('infoDayTitle');
        const events = document.getElementById('infoDayEvents');

        /* Date label */
        title.textContent = new Date(dateStr + 'T00:00:00')
            .toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });

        /* Events */
        const dayEvents = eventsByDate[dateStr] || [];
        if (dayEvents.length === 0) {
            events.innerHTML = `
                <div class="flex flex-col items-center py-10 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                        <i class="fas fa-calendar-xmark text-gray-300 text-2xl"></i>
                    </div>
                    <p class="text-sm text-gray-400 font-medium">Tidak ada jadwal di hari ini</p>
                </div>`;
        } else {
            const colorMap = {
                survey:  { bg:'#dbeafe', text:'#1e40af', label:'Survey' },
                fitting: { bg:'#fce7f3', text:'#be185d', label:'Fitting' },
                hari_h:  { bg:'#fee2e2', text:'#b91c1c', label:'Hari H' },
            };
            const statusMap = {
                scheduled: { label:'Terjadwal', cls:'bg-yellow-100 text-yellow-700' },
                on_going:  { label:'Berlangsung', cls:'bg-blue-100 text-blue-700' },
                finished:  { label:'Selesai', cls:'bg-emerald-100 text-emerald-700' },
                cancelled: { label:'Dibatalkan', cls:'bg-red-100 text-red-700' },
            };
            events.innerHTML = `<div class="space-y-3">` + dayEvents.map(e => {
                const c = colorMap[e.type] || { bg:'#f3f4f6', text:'#374151', label: e.type };
                const st = statusMap[e.status] || { label: e.status || '-', cls:'bg-gray-100 text-gray-600' };
                return `
                <div class="border border-gray-100 rounded-xl p-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-semibold text-gray-800">${e.title}</p>
                        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium" style="background:${c.bg}; color:${c.text};">${c.label}</span>
                        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium ${st.cls}">${st.label}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1.5">
                        ${e.time ? '<i class="fas fa-clock mr-1"></i>' + e.time : ''}
                        ${e.location ? '&nbsp;·&nbsp; <i class="fas fa-location-dot mr-1"></i>' + e.location : ''}
                    </p>
                    <div class="mt-3">
                        <a href="${e.booking_url}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold text-white no-underline transition-colors"
                           style="background:#d4739a;" onmouseover="this.style.background='#b85a82'" onmouseout="this.style.background='#d4739a'">
                            <i class="fas fa-eye"></i> Detail Booking
                        </a>
                    </div>
                </div>`;
            }).join('') + `</div>`;
        }

        /* Tampilkan popup */
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    /* ── Close popup info ── */
    function closeInfo() {
        document.getElementById('dayInfoModal').classList.add('hidden');
        document.body.style.overflow = '';
        if (selectedCell) { selectedCell.classList.remove('is-selected'); selectedCell = null; }
    }

    /* Close popup on backdrop click */
    document.getElementById('dayInfoModal').addEventListener('click', function(e) {
        if (e.target === this) closeInfo();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeInfo();
    });

    /* ── Modal ── */
    function openModal()  { document.getElementById('addModal').classList.remove('hidden'); }
    function closeModal() { document.getElementById('addModal').classList.add('hidden'); }

    /* Close modal on backdrop click */
    document.getElementById('addModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>
@endpush
@endsection
